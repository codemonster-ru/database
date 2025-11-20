<?php

namespace Codemonster\Database;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Exceptions\QueryException;
use Codemonster\Database\Query\QueryBuilder;
use PDO;
use PDOException;

class Connection implements ConnectionInterface
{
    protected PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function select(string $query, array $params = []): array
    {
        return $this->run($query, $params)->fetchAll();
    }

    public function selectOne(string $query, array $params = []): ?array
    {
        $result = $this->run($query, $params)->fetch();

        return $result ?: null;
    }

    public function insert(string $query, array $params = []): bool
    {
        return $this->statement($query, $params);
    }

    public function update(string $query, array $params = []): int
    {
        return $this->run($query, $params)->rowCount();
    }

    public function delete(string $query, array $params = []): int
    {
        return $this->run($query, $params)->rowCount();
    }

    public function statement(string $query, array $params = []): bool
    {
        return $this->run($query, $params) !== false;
    }

    protected function run(string $query, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            return $stmt;
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }
}
