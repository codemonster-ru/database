<?php

namespace Codemonster\Database\Tests\Migrations;

use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Tests\FakeConnection;
use Codemonster\Database\Tests\TestCase;

class RepositoryTest extends TestCase
{
    public function test_repository_logs_migration()
    {
        $conn = new FakeConnection();
        $repo = new MigrationRepository($conn);

        $repo->log('2025_01_01_test', 1);

        $ran = $repo->getRan();

        $this->assertCount(1, $ran);
        $this->assertSame('2025_01_01_test', $ran[0]['migration']);
        $this->assertSame(1, $ran[0]['batch']);
    }
}
