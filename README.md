# codemonster-ru/database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![License](https://img.shields.io/packagist/l/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![Tests](https://github.com/codemonster-ru/database/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/database/actions/workflows/tests.yml)

A lightweight, framework-agnostic database layer for PHP.  
Part of the Codemonster ecosystem — but works fully standalone.

## 📦 Installation

```bash
composer require codemonster-ru/database
```

## 🚀 Usage

### 1. Database Manager

```php
use Codemonster\Database\DatabaseManager;

$manager = new DatabaseManager([
    'default' => 'mysql', // name of the default connection
    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'database' => 'test',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8mb4',
        ],
    ],
]);

$db = $manager->connection(); // default connection
```

You can define multiple connections and select them by name:

```php
$manager = new DatabaseManager([
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'database' => 'app',
            'username' => 'root',
            'password' => '',
        ],
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => __DIR__ . '/database.sqlite',
        ],
    ],
]);

$mysql  = $manager->connection();          // default (mysql)
$sqlite = $manager->connection('sqlite');  // explicit connection
```

-   For **MySQL/MariaDB** use `driver => 'mysql'`.
-   For **SQLite** use `driver => 'sqlite'` and only `database` is required (file path or `:memory:`).
-   Other PDO drivers can be wired via `driver` + DSN-compatible options; the query layer is driver-agnostic, while the schema builder is primarily tuned for MySQL-like syntax and SQLite.

### 2. Query Builder

#### SELECT

```php
$users = $db->table('users')
    ->select('id', 'name', 'email')
    ->where('active', 1)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

#### INSERT

```php
$db->table('users')->insert([
    'name'  => 'Vasya',
    'email' => 'test@example.com',
]);

$id = $db->table('ideas')->insertGetId([
    'title' => 'New idea',
]);
```

#### UPDATE

```php
$db->table('users')
    ->where('id', 5)
    ->update([
        'active'     => 0,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
```

#### DELETE

```php
$db->table('sessions')
    ->where('user_id', 10)
    ->delete();
```

#### Debug SQL

```php
[$sql, $bindings] = $db->table('users')
    ->where('active', 1)
    ->toSql();

// $sql      = 'SELECT * FROM `users` WHERE `active` = ?'
// $bindings = [1]
```

#### Raw expressions

```php
$db->table('users')
    ->selectRaw('COUNT(*) as total')
    ->whereRaw('JSON_VALID(metadata)')
    ->orderByRaw('FIELD(status, "new", "approved", "archived")')
    ->get();
```

#### Join support

```php
$db->table('orders')
    ->join('users', 'users.id', '=', 'orders.user_id')
    ->leftJoin('payments', function ($join) {
        $join->on('payments.order_id', '=', 'orders.id')
             ->where('payments.status', 'paid');
    })
    ->get();
```

#### Group By / Having

```php
$db->table('orders')
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->having('total', '>', 10)
    ->get();
```

#### Aggregates

```php
$count = $db->table('users')->count();
$sum   = $db->table('orders')->sum('amount');
$avg   = $db->table('ratings')->avg('score');
$min   = $db->table('logs')->min('id');
$max   = $db->table('visits')->max('duration');
```

#### Exists

```php
$exists = $db->table('users')
    ->where('email', 'test@example.com')
    ->exists();
```

#### Value / Pluck

```php
$email = $db->table('users')
    ->where('id', 1)
    ->value('email');

$names = $db->table('users')->pluck('name');
$pairs = $db->table('users')->pluck('email', 'id'); // [id => email]
```

#### Pagination

```php
$currentPage = 1;

$page = $db->table('posts')->simplePaginate(20, $currentPage);

// $page = [
//     'data'        => [...],
//     'per_page'    => 20,
//     'current_page'=> 1,
//     'next_page'   => 2,
//     'prev_page'   => null,
// ];
```

### 3. Transactions

```php
$db->transaction(function ($db) {
    $db->table('users')->insert([
        'name'  => 'New user',
        'email' => 'user@example.com',
    ]);

    $db->table('logs')->insert([
        'message' => 'User created',
    ]);
});
```

### 4. Global Helpers (with `codemonster-ru/support`)

If you also install [`codemonster-ru/support`](https://packagist.org/packages/codemonster-ru/support)
and register bindings in your container, you can use global helpers:

```php
db();                 // returns default ConnectionInterface
db('sqlite');         // specific connection
schema();             // schema builder for default connection
transaction(fn() =>   // convenience wrapper
    db()->table('logs')->insert(['message' => 'ok'])
);
```

Helpers are thin wrappers around `DatabaseManager` and the connection’s `schema()` / `transaction()` methods.

## 📐 Schema Builder

The package includes a lightweight schema builder.

> **Note:** The schema grammar is focused on MySQL/MariaDB and SQLite.  
> For other PDO drivers, the query builder will work, but schema operations may not be fully supported.

### Creating a table

```php
use Codemonster\Database\Schema\Blueprint;

$db->schema()->create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->boolean('active')->default(1);
    $table->timestamps();
});
```

### Modifying a table

```php
$db->schema()->table('users', function (Blueprint $table) {
    $table->string('avatar')->nullable();
    $table->integer('age')->default(0);
});
```

### Dropping a table

```php
$db->schema()->drop('users');

// or:
$db->schema()->dropIfExists('users');
```

## 🗄 Supported Column Types

-   Integers: `id`, `integer`, `bigInteger`, `mediumInteger`, `smallInteger`, `tinyInteger`
-   Floats: `decimal`, `double`, `float`
-   Text: `string`, `char`, `text`, `mediumText`, `longText`
-   Boolean: `boolean`
-   JSON: `json`
-   Dates & time: `date`, `datetime`, `timestamp`, `time`, `year`
-   UUID: `uuid`
-   Indexes: `index`, `unique`, `primary`
-   Foreign keys with `foreign()` / `references()` / `on()` and `onDelete()` / `onUpdate()` helpers

## 🚦 Migrations

The package includes a migration system (designed to be used via the CLI).

-   `migrate`
-   `migrate:rollback`
-   `migrate:status`
-   `make:migration`

### Example migration

```php
use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
    }

    public function down(): void
    {
        schema()->drop('posts');
    }
};
```

## 🧰 CLI Tool

A standalone CLI ships with the package:

```bash
vendor/bin/database
```

### Running migrations

```bash
vendor/bin/database migrate
```

### Rollback

```bash
vendor/bin/database migrate:rollback
```

### Status

```bash
vendor/bin/database migrate:status
```

### Create a migration

```bash
vendor/bin/database make:migration CreatePostsTable
```

Default migrations directory:

```text
./database/migrations
```

You can override paths via the migration kernel/path resolver:

```php
$kernel->getPathResolver()->addPath('/path/to/migrations');
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/database/blob/main/LICENSE)
