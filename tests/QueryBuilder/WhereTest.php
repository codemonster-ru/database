<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class WhereTest extends TestCase
{
    public function test_basic_where()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $sql = $qb->where('id', '=', 5)->toSql();

        $this->assertStringContainsString('WHERE `id` = ?', $sql);
    }

    public function test_nested_where_groups()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $qb->where(
            fn($q) =>
            $q->where('age', '>', 18)
                ->orWhere('role', '=', 'admin')
        );

        $sql = $qb->toSql();

        $this->assertStringContainsString(
            "(`age` > ? OR `role` = ?)",
            $sql
        );
    }
}
