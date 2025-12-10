<?php

namespace Codemonster\Database\Tests\ORM;

use PHPUnit\Framework\TestCase;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use Codemonster\Database\ORM\Model;

class ModelTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();

        Model::setConnectionResolver(fn() => $this->conn);

        // seed some rows
        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'Kirill', 'email' => 'k@example.com'],
        ];
    }

    public function test_find_returns_model()
    {
        $user = User::find(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Kirill', $user->name);
    }

    public function test_create_inserts_row()
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com'
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('Test', $user->name);
        $this->assertEquals('test@example.com', $this->conn->tables['users'][1]['email']);
    }

    public function test_save_updates_existing()
    {
        $user = User::find(1);
        $user->name = 'Updated';
        $user->save();

        $this->assertEquals('Updated', $this->conn->tables['users'][0]['name']);
    }
}
