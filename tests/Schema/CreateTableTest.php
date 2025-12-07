<?php

namespace Codemonster\Database\Tests\QueryBuilder;

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Schema\MySqlGrammar;
use Codemonster\Database\Tests\TestCase;

class CreateTableTest extends TestCase
{
    public function test_create_table_grammar()
    {
        $grammar = new MySqlGrammar();
        $blueprint = new Blueprint('users');

        $blueprint->id();
        $blueprint->string('name');
        $blueprint->timestamps();

        $sql = $grammar->compileCreate($blueprint);

        $this->assertStringContainsString('CREATE TABLE `users`', $sql[0]);
        $this->assertStringContainsString('`id` INT UNSIGNED AUTO_INCREMENT NOT NULL', $sql[0]);
    }
}
