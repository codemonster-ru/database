<?php

namespace Codemonster\Database\Contracts;

use Codemonster\Database\Query\QueryBuilder;

interface ConnectionInterface
{
    public function select(string $query, array $params = []): array;

    public function selectOne(string $query, array $params = []): ?array;

    public function insert(string $query, array $params = []): bool;

    public function update(string $query, array $params = []): int;

    public function delete(string $query, array $params = []): int;

    public function statement(string $query, array $params = []): bool;

    public function getPdo(): \PDO;

    public function table(string $table): QueryBuilder;
}
