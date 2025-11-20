# codemonster-ru/database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![License](https://img.shields.io/packagist/l/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![Tests](https://github.com/codemonster-ru/database/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/database/actions/workflows/tests.yml)

A lightweight database package built on top of PDO.
Part of the Codemonster ecosystem, it's completely independent and can be used in any PHP project.

## 📦 Installation

```bash
composer require codemonster-ru/database
```

## 🚀 Usage

### Raw SQL (basic usage)

```php
use Codemonster\Database\DatabaseManager;

// Initialization example
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
$users = $db->select("SELECT * FROM users WHERE active = ?", [1]);
```

### Query Builder (SELECT)

```php
$users = $db->table('users')
    ->select('id', 'name', 'email')
    ->where('active', 1)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

### Get a single row

```php
$user = $db->table('users')
    ->where('id', 5)
    ->first();
```

### Debug SQL

```php
$sql = $db->table('users')
    ->where('active', 1)
    ->toSql();

$bindings = $db->table('users')
    ->where('active', 1)
    ->getBindings();
```

## 👨‍💻 Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## 📜 License

[MIT](https://github.com/codemonster-ru/database/blob/main/LICENSE)
