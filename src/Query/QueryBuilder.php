<?php

namespace Codemonster\Database\Query;

use Codemonster\Database\Contracts\ConnectionInterface;

class QueryBuilder
{
    protected ConnectionInterface $connection;

    protected string $table;

    protected array $columns = ['*'];

    protected array $wheres = [];

    protected array $orders = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    public function __construct(ConnectionInterface $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function select(string ...$columns): self
    {
        if (count($columns) === 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }

        if (empty($columns)) {
            $columns = ['*'];
        }

        $this->columns = $columns;

        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null, string $boolean = 'and'): self
    {
        if ($value === null) {
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            $operator = $operatorOrValue;
        }

        $this->wheres[] = [
            'type'     => 'basic',
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value,
            'boolean'  => strtolower($boolean) === 'or' ? 'or' : 'and',
        ];

        return $this;
    }

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        return $this->where($column, $operatorOrValue, $value, 'or');
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $this->orders[] = [
            'column'    => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function limit(int $value): self
    {
        $this->limit = max(0, $value);

        return $this;
    }

    public function offset(int $value): self
    {
        $this->offset = max(0, $value);

        return $this;
    }

    public function get(): array
    {
        [$sql, $bindings] = $this->compileSelect();

        return $this->connection->select($sql, $bindings);
    }

    public function first(): ?array
    {
        $this->limit ??= 1;

        [$sql, $bindings] = $this->compileSelect();

        return $this->connection->selectOne($sql, $bindings);
    }

    public function toSql(): string
    {
        return $this->compileSelect()[0];
    }

    public function getBindings(): array
    {
        return $this->compileSelect()[1];
    }

    public function insert(array $values): bool
    {
        [$sql, $bindings] = $this->compileInsert($values);

        return $this->connection->insert($sql, $bindings);
    }

    public function insertGetId(array $values): int
    {
        [$sql, $bindings] = $this->compileInsert($values);

        $this->connection->insert($sql, $bindings);

        return (int) $this->connection->getPdo()->lastInsertId();
    }

    protected function compileInsert(array $values): array
    {
        $columns = array_keys($values);

        $wrapped = implode(', ', array_map([$this, 'wrapColumn'], $columns));

        $placeholders = rtrim(str_repeat('?, ', count($values)), ', ');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->wrapTable($this->table),
            $wrapped,
            $placeholders
        );

        return [$sql, array_values($values)];
    }

    public function update(array $values): int
    {
        [$sql, $bindings] = $this->compileUpdate($values);

        return $this->connection->update($sql, $bindings);
    }

    protected function compileUpdate(array $values): array
    {
        $setParts = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $setParts[] = $this->wrapColumn($column) . ' = ?';
            $bindings[] = $value;
        }

        $sql = 'UPDATE ' . $this->wrapTable($this->table)
            . ' SET ' . implode(', ', $setParts);

        if (!empty($this->wheres)) {
            [$whereSql, $whereBindings] = $this->compileWheres();
            $sql .= ' WHERE ' . $whereSql;
            $bindings = array_merge($bindings, $whereBindings);
        }

        return [$sql, $bindings];
    }

    public function delete(): int
    {
        [$sql, $bindings] = $this->compileDelete();

        return $this->connection->delete($sql, $bindings);
    }

    protected function compileDelete(): array
    {
        $sql = 'DELETE FROM ' . $this->wrapTable($this->table);
        $bindings = [];

        if (!empty($this->wheres)) {
            [$whereSql, $whereBindings] = $this->compileWheres();
            $sql .= ' WHERE ' . $whereSql;
            $bindings = $whereBindings;
        }

        return [$sql, $bindings];
    }

    protected function compileSelect(): array
    {
        $sql = 'SELECT ' . $this->compileColumns()
            . ' FROM ' . $this->wrapTable($this->table);

        $bindings = [];

        if (!empty($this->wheres)) {
            [$whereSql, $whereBindings] = $this->compileWheres();
            $sql .= ' WHERE ' . $whereSql;
            $bindings = array_merge($bindings, $whereBindings);
        }

        if (!empty($this->orders)) {
            $sql .= ' ' . $this->compileOrders();
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int) $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . (int) $this->offset;
        }

        return [$sql, $bindings];
    }

    protected function compileColumns(): string
    {
        if ($this->columns === ['*']) {
            return '*';
        }

        return implode(', ', array_map([$this, 'wrapColumn'], $this->columns));
    }

    protected function compileWheres(): array
    {
        $sqlParts = [];
        $bindings = [];

        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? '' : ' ' . strtoupper($where['boolean']) . ' ';
            $sqlParts[] = $prefix
                . $this->wrapColumn($where['column']) . ' '
                . $where['operator'] . ' ?';
            $bindings[] = $where['value'];
        }

        return [implode('', $sqlParts), $bindings];
    }

    protected function compileOrders(): string
    {
        $parts = [];

        foreach ($this->orders as $order) {
            $parts[] = $this->wrapColumn($order['column'])
                . ' ' . strtoupper($order['direction']);
        }

        return 'ORDER BY ' . implode(', ', $parts);
    }

    protected function wrapTable(string $table): string
    {
        return '`' . str_replace('`', '``', $table) . '`';
    }

    protected function wrapColumn(string $column): string
    {
        if ($column === '*') {
            return '*';
        }

        if (str_contains($column, '.')) {
            [$table, $col] = explode('.', $column, 2);

            return $this->wrapTable($table) . '.' . $this->wrapColumn($col);
        }

        return '`' . str_replace('`', '``', $column) . '`';
    }
}
