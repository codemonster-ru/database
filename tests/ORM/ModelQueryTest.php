<?php

namespace Codemonster\Database\Tests\ORM;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class ModelQueryTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();

        Model::setConnectionResolver(fn() => $this->conn);

        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second'],
        ];
    }

    public function test_get_returns_model_collection()
    {
        $users = User::all();

        $this->assertInstanceOf(ModelCollection::class, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertCount(2, $users);
    }

    public function test_first_applies_where()
    {
        $user = User::query()
            ->where('id', 2)
            ->first();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Second', $user->name);
    }

    public function test_count_and_exists()
    {
        $this->assertTrue(User::query()->exists());
        $this->assertEquals(2, User::query()->count());

        $this->assertFalse(
            User::query()->where('name', 'Missing')->exists()
        );
    }
}
