<?php

namespace Codemonster\Database\Tests;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Query\QueryBuilder;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createPdo(string $dsn, array $config, array $options): \PDO
    {
        return new \PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $options
        );
    }

    protected function fakeConnection(): ConnectionInterface
    {
        return new class implements ConnectionInterface {

            public array $executed = [];

            public function select(string $query, array $params = []): array
            {
                $this->executed[] = [$query, $params];

                return [];
            }

            public function selectOne(string $query, array $params = []): ?array
            {
                $this->executed[] = [$query, $params];

                return null;
            }

            public function insert(string $query, array $params = []): bool
            {
                $this->executed[] = [$query, $params];

                return true;
            }

            public function update(string $query, array $params = []): int
            {
                $this->executed[] = [$query, $params];

                return 1;
            }

            public function delete(string $query, array $params = []): int
            {
                $this->executed[] = [$query, $params];

                return 1;
            }

            public function statement(string $query, array $params = []): bool
            {
                $this->executed[] = [$query, $params];

                return true;
            }

            public function table(string $table): QueryBuilder
            {
                return new QueryBuilder($this, $table);
            }

            public function beginTransaction(): bool
            {
                return true;
            }
            public function commit(): bool
            {
                return true;
            }
            public function rollBack(): bool
            {
                return true;
            }

            public function transaction(callable $callback): mixed
            {
                return $callback($this);
            }

            public function getPdo(): PDO
            {
                throw new \RuntimeException("PDO not used in QueryBuilder tests");
            }
        };
    }
}
