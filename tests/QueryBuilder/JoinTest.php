<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Tests\TestCase;

class JoinTest extends TestCase
{
    public function test_inner_join()
    {
        $qb = new QueryBuilder($this->fakeConnection(), 'users');

        $qb->join(
            'posts',
            fn($j) =>
            $j->on('users.id', '=', 'posts.user_id')
        );

        $sql = $qb->toSql();

        $this->assertStringContainsString(
            'INNER JOIN `posts` ON `users`.`id` = `posts`.`user_id`',
            $sql
        );
    }
}
