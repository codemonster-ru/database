<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class AggregateTest extends TestCase
{
    public function test_count_does_not_mutate_builder()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');
        $qb->where('active', '=', 1);

        try {
            $qb->count();
        } catch (\Throwable $e) {
        }

        $sql = $qb->toSql();

        $this->assertStringNotContainsString('COUNT', $sql);
    }
}
