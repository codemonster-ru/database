<?php

namespace Codemonster\Database\Schema\Grammars;

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Schema\ColumnDefinition;
use Codemonster\Database\Schema\ForeignKeyDefinition;
use Codemonster\Database\Schema\Grammar;

class MySqlGrammar extends Grammar
{
    public function compileCreate(Blueprint $blueprint): array
    {
        $columns = array_map(
            fn($col) => $this->compileColumn($col),
            $blueprint->columns
        );

        // Inline foreign keys
        foreach ($blueprint->foreignKeys as $fk) {
            $columns[] = $this->compileInlineForeign($blueprint, $fk);
        }

        // Indexes (primary, unique, index)
        foreach ($blueprint->indexes as $index) {
            $columns[] = $this->compileIndex($index);
        }

        $columnsSql = implode(", ", $columns);

        return [
            "CREATE TABLE `{$blueprint->table}` ({$columnsSql}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];
    }

    public function compileDrop(string $table): array
    {
        return ["DROP TABLE `{$table}`"];
    }

    public function compileDropIfExists(string $table): array
    {
        return ["DROP TABLE IF EXISTS `{$table}`"];
    }

    public function compileAlter(Blueprint $blueprint): array
    {
        $sql = [];

        // ADD COLUMN
        foreach ($blueprint->columns as $col) {
            $sql[] = "ALTER TABLE `{$blueprint->table}` ADD " . $this->compileColumn($col);
        }

        // DROP COLUMN
        foreach ($blueprint->dropColumns as $name) {
            $sql[] = "ALTER TABLE `{$blueprint->table}` DROP COLUMN `{$name}`";
        }

        // DROP FOREIGN
        foreach ($blueprint->dropForeignKeys as $name) {
            $sql[] = "ALTER TABLE `{$blueprint->table}` DROP FOREIGN KEY `{$name}`";
        }

        // DROP INDEX
        foreach ($blueprint->dropIndexes as $name) {
            $sql[] = "ALTER TABLE `{$blueprint->table}` DROP INDEX `{$name}`";
        }

        // DROP PRIMARY
        foreach ($blueprint->dropPrimaryKeys as $name) {
            $sql[] = "ALTER TABLE `{$blueprint->table}` DROP PRIMARY KEY";
        }

        // RENAME COLUMNS
        foreach ($blueprint->renameColumns as $rename) {
            $sql[] = "ALTER TABLE `{$blueprint->table}` RENAME COLUMN `{$rename['from']}` TO `{$rename['to']}`";
        }

        // RENAME TABLE
        if ($blueprint->renameTable !== null) {
            $sql[] = "RENAME TABLE `{$blueprint->table}` TO `{$blueprint->renameTable}`";
        }

        // FOREIGN KEYS (ALTER)
        foreach ($blueprint->foreignKeys as $fk) {
            $sql[] = $this->compileForeign($blueprint, $fk);
        }

        // INDEXES
        foreach ($blueprint->indexes as $index) {
            $sql[] = $this->compileAlterIndex($blueprint, $index);
        }

        return $sql;
    }

    public function compileRenameTable(Blueprint $blueprint): array
    {
        return [
            "RENAME TABLE `{$blueprint->table}` TO `{$blueprint->renameTable}`"
        ];
    }

    protected function compileColumn(ColumnDefinition $col): string
    {
        $type = $this->mapType($col);

        $sql = "`{$col->name}` {$type}";

        // UNSIGNED
        if (($col->modifiers['unsigned'] ?? false) === true) {
            $sql .= " UNSIGNED";
        }

        // NULL / NOT NULL
        $nullable = $col->modifiers['nullable'] ?? true;
        $sql .= $nullable ? " NULL" : " NOT NULL";

        // DEFAULT
        if (array_key_exists('default', $col->modifiers)) {
            $value = $col->modifiers['default'];
            $default = is_string($value) ? "'{$value}'" : $value;
            $sql .= " DEFAULT {$default}";
        }

        // AUTO_INCREMENT
        if (($col->modifiers['autoIncrement'] ?? false) === true) {
            $sql .= " AUTO_INCREMENT";
        }

        // UNIQUE (inline only for CREATE TABLE)
        if (($col->modifiers['unique'] ?? false) === true) {
            $sql .= " UNIQUE";
        }

        // PRIMARY (inline only for CREATE TABLE)
        if (($col->modifiers['primary'] ?? false) === true) {
            $sql .= " PRIMARY KEY";
        }

        // COMMENT
        if (isset($col->modifiers['comment'])) {
            $comment = addslashes($col->modifiers['comment']);
            $sql .= " COMMENT '{$comment}'";
        }

        return $sql;
    }

    protected function mapType(ColumnDefinition $col): string
    {
        return match ($col->type) {

            'id' => 'BIGINT UNSIGNED',

            'string' => sprintf(
                'VARCHAR(%d)',
                $col->options['length'] ?? 255
            ),

            'char' => sprintf(
                'CHAR(%d)',
                $col->options['length'] ?? 255
            ),

            'integer' => 'INT',
            'bigInteger' => 'BIGINT',
            'mediumInteger' => 'MEDIUMINT',
            'smallInteger' => 'SMALLINT',
            'tinyInteger' => 'TINYINT',

            'boolean' => 'TINYINT(1)',
            'text' => 'TEXT',
            'mediumText' => 'MEDIUMTEXT',
            'longText' => 'LONGTEXT',
            'json' => 'JSON',

            'decimal' => sprintf(
                'DECIMAL(%d,%d)',
                $col->options['precision'] ?? 8,
                $col->options['scale'] ?? 2
            ),

            'double' => sprintf(
                'DOUBLE(%d,%d)',
                $col->options['precision'] ?? 8,
                $col->options['scale'] ?? 2
            ),

            'float' => sprintf(
                'FLOAT(%d,%d)',
                $col->options['precision'] ?? 8,
                $col->options['scale'] ?? 2
            ),

            'timestamp' => 'TIMESTAMP',
            'datetime' => 'DATETIME',
            'date' => 'DATE',
            'time' => 'TIME',
            'year' => 'YEAR',

            'uuid' => 'CHAR(36)',

            default => strtoupper($col->type),
        };
    }

    protected function compileInlineForeign(Blueprint $blueprint, ForeignKeyDefinition $fk): string
    {
        return sprintf(
            "FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`)",
            $fk->column,
            $fk->on,
            $fk->references
        );
    }

    protected function compileForeign(Blueprint $blueprint, ForeignKeyDefinition $fk): string
    {
        $sql = sprintf(
            "ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`)",
            $blueprint->table,
            $fk->name ?? "{$blueprint->table}_{$fk->column}_foreign",
            $fk->column,
            $fk->on,
            $fk->references
        );

        // Optional ON DELETE / ON UPDATE
        if ($fk->onDelete) {
            $sql .= " ON DELETE {$fk->onDelete}";
        }

        if ($fk->onUpdate) {
            $sql .= " ON UPDATE {$fk->onUpdate}";
        }

        return $sql;
    }

    protected function compileIndex(array $index): string
    {
        $cols = '`' . implode('`,`', $index['columns']) . '`';
        $name = $index['name'] ?? implode('_', $index['columns']);

        return match ($index['type']) {
            'primary' => "PRIMARY KEY ({$cols})",
            'unique'  => "UNIQUE KEY `{$name}` ({$cols})",
            default   => "KEY `{$name}` ({$cols})",
        };
    }

    protected function compileAlterIndex(Blueprint $blueprint, array $index): string
    {
        $cols = '`' . implode('`,`', $index['columns']) . '`';
        $name = $index['name'] ?? implode('_', $index['columns']);

        return match ($index['type']) {
            'primary' => "ALTER TABLE `{$blueprint->table}` ADD PRIMARY KEY ({$cols})",
            'unique'  => "ALTER TABLE `{$blueprint->table}` ADD UNIQUE `{$name}` ({$cols})",
            default   => "ALTER TABLE `{$blueprint->table}` ADD INDEX `{$name}` ({$cols})",
        };
    }
}
