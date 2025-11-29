<?php

namespace Codemonster\Database\Query;

use Codemonster\Database\Contracts\ConnectionInterface;

class QueryBuilder
{
    protected ConnectionInterface $connection;

    protected string $table;

    /** @var array<int, string|RawExpression> */
    protected array $columns = ['*'];

    /** @var array<int, array<string, mixed>> */
    protected array $orders = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    protected WhereGroup $where;

    /** @var JoinClause[] */
    protected array $joins = [];

    protected bool $distinct = false;

    /** @var string[] */
    protected array $groups = [];

    /** @var array<int, array<string, mixed>> */
    protected array $havings = [];

    public function __construct(ConnectionInterface $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->where = new WhereGroup();
    }

    // ------------------------------------------------------
    // Basic query setup
    // ------------------------------------------------------

    public function select(string|array ...$columns): self
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

    public function selectRaw(string $expression): static
    {
        $this->columns[] = new RawExpression($expression);
        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    // ------------------------------------------------------
    // WHERE
    // ------------------------------------------------------

    public function where(string|callable $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        if (is_callable($column)) {
            $group = new WhereGroup();
            $column($this->newScopedBuilder($group));
            $this->where->addGroup($group, strtoupper($boolean));
            return $this;
        }

        // where('active', 1) → '='
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $condition = new WhereCondition(
            $column,
            $operator,
            $value,
            strtoupper($boolean)
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhere(string|callable $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'IN',
            $values,
            $boolean,
            isList: true
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'NOT IN',
            $values,
            $boolean,
            isList: true
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereNotIn(string $column, array $values): static
    {
        return $this->whereNotIn($column, $values, 'OR');
    }

    public function whereNull(string $column, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'IS NULL',
            value: null,
            boolean: $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'OR');
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'IS NOT NULL',
            value: null,
            boolean: $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        return $this->whereNotNull($column, 'OR');
    }

    public function whereBetween(string $column, array $range, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'BETWEEN',
            $range,
            $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereBetween(string $column, array $range): static
    {
        return $this->whereBetween($column, $range, 'OR');
    }

    public function whereNotBetween(string $column, array $range, string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            $column,
            'NOT BETWEEN',
            $range,
            $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereNotBetween(string $column, array $range): static
    {
        return $this->whereNotBetween($column, $range, 'OR');
    }

    public function whereRaw(string $expression, array $bindings = [], string $boolean = 'AND'): static
    {
        $condition = new WhereCondition(
            column: '',
            operator: 'RAW',
            value: [$expression, $bindings],
            boolean: $boolean
        );

        $this->where->addCondition($condition);

        return $this;
    }

    public function orWhereRaw(string $expression, array $bindings = []): static
    {
        return $this->whereRaw($expression, $bindings, 'OR');
    }

    // ------------------------------------------------------
    // JOIN
    // ------------------------------------------------------

    public function join(string $table, string|callable $first, ?string $operator = null, ?string $second = null, string $type = 'INNER'): static
    {
        $join = new JoinClause($type, $table);

        if (is_callable($first)) {
            $first($join);
        } else {
            $join->on($first, $operator, $second);
        }

        $this->joins[] = $join;

        return $this;
    }

    public function leftJoin(string $table, string|callable $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string|callable $first, ?string $operator = null, ?string $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function crossJoin(string $table): static
    {
        $this->joins[] = new JoinClause('CROSS', $table);
        return $this;
    }

    protected function compileJoins(array &$bindings): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = '';

        foreach ($this->joins as $join) {
            $sql .= ' ' . $join->type . ' JOIN ' . $this->wrapTable($join->table);

            $conditions = [];

            foreach ($join->conditions as $cond) {
                if ($cond['type'] === 'on') {
                    $conditions[] = sprintf(
                        '%s %s %s',
                        $this->wrapColumn($cond['first']),
                        $cond['operator'],
                        $this->wrapColumn($cond['second'])
                    );
                }

                if ($cond['type'] === 'where') {
                    $conditions[] = sprintf(
                        '%s %s ?',
                        $this->wrapColumn($cond['column']),
                        $cond['operator']
                    );
                    $bindings[] = $cond['value'];
                }
            }

            if ($conditions) {
                $sql .= ' ON ' . implode(' AND ', $conditions);
            }
        }

        return $sql;
    }

    // ------------------------------------------------------
    // GROUP BY / HAVING
    // ------------------------------------------------------

    public function groupBy(string|array $columns): static
    {
        foreach ((array) $columns as $column) {
            $this->groups[] = $column;
        }

        return $this;
    }

    public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): static
    {
        $this->havings[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => strtoupper($boolean),
        ];

        return $this;
    }

    public function orHaving(string $column, string $operator, mixed $value): static
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    public function havingRaw(string $expression, string $boolean = 'AND'): static
    {
        $this->havings[] = [
            'type' => 'raw',
            'sql' => $expression,
            'boolean' => strtoupper($boolean),
        ];

        return $this;
    }

    // ------------------------------------------------------
    // ORDER / LIMIT / OFFSET
    // ------------------------------------------------------

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'desc' ? 'DESC' : 'ASC',
        ];

        return $this;
    }

    public function orderByRaw(string $expression): static
    {
        $this->orders[] = [
            'raw' => $expression,
            'type' => 'raw'
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

    // ------------------------------------------------------
    // FETCHING
    // ------------------------------------------------------

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

    // ------------------------------------------------------
    // INSERT
    // ------------------------------------------------------

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

    // ------------------------------------------------------
    // UPDATE
    // ------------------------------------------------------

    public function update(array $values): int
    {
        [$sql, $bindings] = $this->compileUpdate($values);
        return $this->connection->update($sql, $bindings);
    }

    protected function compileUpdate(array $values): array
    {
        $set = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $set[] = $this->wrapColumn($column) . ' = ?';
            $bindings[] = $value;
        }

        $sql = 'UPDATE ' . $this->wrapTable($this->table)
            . ' SET ' . implode(', ', $set);

        $whereSql = $this->compileWhere($this->where, $bindings);

        if ($whereSql) {
            $sql .= ' WHERE ' . $whereSql;
        }

        return [$sql, $bindings];
    }

    // ------------------------------------------------------
    // DELETE
    // ------------------------------------------------------

    public function delete(): int
    {
        [$sql, $bindings] = $this->compileDelete();
        return $this->connection->delete($sql, $bindings);
    }

    protected function compileDelete(): array
    {
        $bindings = [];
        $sql = 'DELETE FROM ' . $this->wrapTable($this->table);

        $whereSql = $this->compileWhere($this->where, $bindings);

        if ($whereSql) {
            $sql .= ' WHERE ' . $whereSql;
        }

        return [$sql, $bindings];
    }

    // ------------------------------------------------------
    // SELECT compiler
    // ------------------------------------------------------

    protected function compileSelect(): array
    {
        $bindings = [];

        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= $this->compileColumns();
        $sql .= ' FROM ' . $this->wrapTable($this->table);

        // JOINs
        $joins = $this->compileJoins($bindings);
        if ($joins) {
            $sql .= $joins;
        }

        // WHERE
        $whereSql = $this->compileWhere($this->where, $bindings);
        if ($whereSql) {
            $sql .= ' WHERE ' . $whereSql;
        }

        // GROUP BY
        if (!empty($this->groups)) {
            $cols = array_map([$this, 'wrapColumn'], $this->groups);
            $sql .= ' GROUP BY ' . implode(', ', $cols);
        }

        // HAVING
        if (!empty($this->havings)) {
            $havingParts = [];

            foreach ($this->havings as $i => $having) {
                $boolean = $i === 0 ? '' : $having['boolean'] . ' ';

                if ($having['type'] === 'raw') {
                    $havingParts[] = $boolean . $having['sql'];
                    continue;
                }

                if ($having['type'] === 'basic') {
                    $bindings[] = $having['value'];

                    $havingParts[] = sprintf(
                        '%s%s %s ?',
                        $boolean,
                        $this->wrapColumn($having['column']),
                        $having['operator']
                    );
                }
            }

            if ($havingParts) {
                $sql .= ' HAVING ' . implode(' ', $havingParts);
            }
        }

        // ORDER BY
        if (!empty($this->orders)) {
            $parts = [];

            foreach ($this->orders as $order) {
                if (($order['type'] ?? null) === 'raw') {
                    $parts[] = $order['raw'];
                } else {
                    $parts[] = $this->wrapColumn($order['column']) . ' ' . $order['direction'];
                }
            }

            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }

        // LIMIT / OFFSET
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
        $parts = [];

        foreach ($this->columns as $col) {
            if ($col instanceof RawExpression) {
                $parts[] = $col->getValue();
            } else {
                $parts[] = $this->wrapColumn($col);
            }
        }

        if (empty($parts)) {
            return '*';
        }

        return implode(', ', $parts);
    }

    // ------------------------------------------------------
    // WHERE compilation
    // ------------------------------------------------------

    protected function compileWhere(WhereGroup $group, array &$bindings): ?string
    {
        if ($group->isEmpty()) {
            return null;
        }

        $sqlParts = [];

        foreach ($group->items as $i => $item) {
            $boolean = $i === 0 ? '' : $item['boolean'] . ' ';

            if ($item['type'] === 'condition') {
                /** @var WhereCondition $cond */
                $cond = $item['condition'];

                // RAW
                if ($cond->operator === 'RAW') {
                    [$expr, $params] = $cond->value;

                    foreach ($params as $p) {
                        $bindings[] = $p;
                    }

                    $sqlParts[] = $boolean . '(' . $expr . ')';
                    continue;
                }

                // IS NULL / IS NOT NULL
                if ($cond->operator === 'IS NULL' || $cond->operator === 'IS NOT NULL') {
                    $sqlParts[] = $boolean . sprintf(
                        '`%s` %s',
                        $cond->column,
                        $cond->operator
                    );
                    continue;
                }

                // BETWEEN / NOT BETWEEN
                if ($cond->operator === 'BETWEEN' || $cond->operator === 'NOT BETWEEN') {
                    $bindings[] = $cond->value[0];
                    $bindings[] = $cond->value[1];

                    $sqlParts[] = $boolean . sprintf(
                        '`%s` %s ? AND ?',
                        $cond->column,
                        $cond->operator
                    );
                    continue;
                }

                // IN / NOT IN
                if ($cond->isList && ($cond->operator === 'IN' || $cond->operator === 'NOT IN')) {
                    $placeholders = implode(', ', array_fill(0, count($cond->value), '?'));

                    foreach ($cond->value as $v) {
                        $bindings[] = $v;
                    }

                    $sqlParts[] = $boolean . sprintf(
                        '`%s` %s (%s)',
                        $cond->column,
                        $cond->operator,
                        $placeholders
                    );

                    continue;
                }

                // Обычное условие
                $bindings[] = $cond->value;

                $sqlParts[] =
                    $boolean . sprintf('`%s` %s ?', $cond->column, $cond->operator);
            }

            if ($item['type'] === 'group') {
                $nested = $this->compileWhere($item['group'], $bindings);
                if ($nested !== null) {
                    $sqlParts[] = $boolean . '(' . $nested . ')';
                }
            }
        }

        return implode(' ', $sqlParts);
    }

    // ------------------------------------------------------
    // Aggregates
    // ------------------------------------------------------

    protected function aggregate(string $function, string $column = '*'): mixed
    {
        $alias = '_aggregate';

        $this->columns = [new RawExpression(
            sprintf('%s(%s) as %s', $function, $this->wrapColumn($column), $alias)
        )];

        $this->orders = [];
        $this->limit = null;
        $this->offset = null;

        [$sql, $bindings] = $this->compileSelect();

        $row = $this->connection->selectOne($sql, $bindings);

        if (!$row) {
            return null;
        }

        return $row[$alias] ?? null;
    }

    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    public function sum(string $column): float|int
    {
        return $this->aggregate('SUM', $column);
    }

    public function avg(string $column): float|int
    {
        return $this->aggregate('AVG', $column);
    }

    public function min(string $column): float|int
    {
        return $this->aggregate('MIN', $column);
    }

    public function max(string $column): float|int
    {
        return $this->aggregate('MAX', $column);
    }

    public function exists(): bool
    {
        $clone = clone $this;

        $clone->columns = [new RawExpression('1')];
        $clone->limit = 1;

        [$sql, $bindings] = $clone->compileSelect();

        return (bool) $this->connection->selectOne($sql, $bindings);
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    // ------------------------------------------------------
    // Helpers: value / pluck / pagination
    // ------------------------------------------------------

    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();

        if (!$result) {
            return null;
        }

        return $result[$column] ?? null;
    }

    public function pluck(string $column, ?string $key = null): array
    {
        $this->select($key ? [$column, $key] : [$column]);

        $rows = $this->get();
        $result = [];

        foreach ($rows as $row) {
            if ($key) {
                $result[$row[$key]] = $row[$column];
            } else {
                $result[] = $row[$column];
            }
        }

        return $result;
    }

    public function forPage(int $page, int $perPage): static
    {
        $page = max(1, $page);

        $this->limit($perPage);
        $this->offset(($page - 1) * $perPage);

        return $this;
    }

    public function simplePaginate(int $perPage = 15, int $page = 1): array
    {
        $page = max(1, $page);

        $this->limit($perPage + 1);
        $this->offset(($page - 1) * $perPage);

        $rows = $this->get();

        $hasMore = count($rows) > $perPage;

        $data = array_slice($rows, 0, $perPage);

        return [
            'data' => $data,
            'per_page' => $perPage,
            'current_page' => $page,
            'next_page' => $hasMore ? $page + 1 : null,
            'prev_page' => $page > 1 ? $page - 1 : null,
        ];
    }

    // ------------------------------------------------------
    // Low-level helpers
    // ------------------------------------------------------

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

    protected function newScopedBuilder(WhereGroup $group): static
    {
        $clone = clone $this;
        $clone->where = $group;

        return $clone;
    }
}
