<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class SelectTest extends TestCase
{
    public function test_simple_select()
    {
        $connection = $this->fakeConnection();

        $qb = new QueryBuilder($connection, 'users');

        $sql = $qb->toSql();

        $this->assertEquals('SELECT * FROM `users`', $sql);
    }
}
