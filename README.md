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
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'test',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
    ],
]);

$db = $manager->connection();
```

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
    'name' => 'Vasya',
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
        'active' => 0,
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
    ->leftJoin('payments', fn($join) =>
        $join->on('payments.order_id', '=', 'orders.id')
             ->where('payments.status', 'paid')
    )
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
$db->table('users')->where('email', 'test@example.com')->exists();
```

#### Value / Pluck

```php
$email = $db->table('users')->where('id', 1)->value('email');

$names = $db->table('users')->pluck('name');
$pairs = $db->table('users')->pluck('email', 'id');
```

#### Pagination

```php
$page = $db->table('posts')->simplePaginate(20, $currentPage);
```

### 3. Transactions

```php
$db->transaction(function ($db) {
    $db->table('users')->insert([...]);
    $db->table('logs')->insert([...]);
});
```

## 📐 Schema Builder

The package includes a lightweight schema builder:

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
```

## 🗄 Supported Column Types

-   Integers: `id`, `integer`, `bigInteger`, `mediumInteger`, `smallInteger`, `tinyInteger`
-   Floats: `decimal`, `double`, `float`
-   Text: `string`, `char`, `text`, `mediumText`, `longText`
-   `boolean`
-   `json`
-   Dates: `date`, `datetime`, `timestamp`, `time`, `year`
-   `uuid`
-   Indexes: `index`, `unique`, `primary`
-   Foreign keys

## 🚦 Migrations

The package includes a full migration system with:

-   `migrate`
-   `migrate:rollback`
-   `migrate:status`
-   `make:migration`

### Example migration

```php
return new class extends Migration {
    public function up() {
        schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
    }

    public function down() {
        schema()->drop('posts');
    }
};
```

## 🧰 CLI Tool

A standalone CLI ships with the package:

```
vendor/bin/database
```

### Running migrations

```
vendor/bin/database migrate
```

### Rollback

```
vendor/bin/database migrate:rollback
```

### Status

```
vendor/bin/database migrate:status
```

### Create a migration

```
vendor/bin/database make:migration CreatePostsTable
```

Default migrations directory:

```
./database/migrations
```

You can override paths via:

```php
$kernel->getPathResolver()->addPath('/path/to/migrations');
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/support/database/main/LICENSE)
