<?php

namespace Codemonster\Database\Tests;

use Codemonster\Database\Connection;
use Codemonster\Database\Exceptions\QueryException;

class ConnectionTest extends TestCase
{
    public function test_connection_throws_query_exception_on_bad_credentials()
    {
        $this->expectException(QueryException::class);

        new Connection([
            'host' => '127.0.0.1',
            'database' => 'wrong',
            'username' => 'root',
            'password' => 'nope',
        ]);
    }

    public function test_select_queries_are_prepared()
    {
        $pdo = new class {
            public array $executed = [];

            public function prepare($query)
            {
                $this->executed[] = $query;

                return new class extends \PDOStatement {
                    public function execute(?array $params = null): bool
                    {
                        return true;
                    }

                    public function fetchAll(int $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, ...$args): array
                    {
                        return [];
                    }
                };
            }
        };

        $ref = new \ReflectionClass(Connection::class);

        /** @var Connection $connection */
        $connection = $ref->newInstanceWithoutConstructor();

        $prop = $ref->getProperty('pdo');
        $prop->setAccessible(true);
        $prop->setValue($connection, $pdo);

        $connection->select('SELECT * FROM users');

        $this->assertEquals(
            ['SELECT * FROM users'],
            $pdo->executed
        );
    }
}
