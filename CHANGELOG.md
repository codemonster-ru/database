# Changelog

All significant changes to this project will be documented in this file.

## [0.5.0] – 2025-11-29

### Added

-   Expanded Schema Builder with full support for MySQL data types:
    -   Integer types: `bigInteger`, `mediumInteger`, `smallInteger`, `tinyInteger`
    -   Decimal & floating types: `decimal`, `double`, `float`
    -   String & text types: `char`, `mediumText`, `longText`
    -   JSON type: `json`
    -   Date & time types: `date`, `datetime`, `time`, `year`
    -   UUID type: `uuid`
-   Added modifier support for:
    -   `unsigned`
    -   `autoIncrement`
    -   `comment`
    -   `change()` for modifying existing columns

### Changed

-   Reworked `compileType()` in `MySqlGrammar` to support all new types.
-   Updated `Blueprint` to include methods for new column types.
-   Standardized Schema Builder to return array of SQL statements for all operations.
-   Improved overall consistency of CREATE/ALTER/DROP SQL generation.

### Fixed

-   Removed obsolete `$commands` from `Blueprint`.
-   Fixed handling of NOT NULL / NULL defaults.
-   Correct handling of PRIMARY/UNIQUE via indexes instead of column modifiers.

## [0.4.0] - 2025-11-21

### Added

-   Transaction support in `Connection` and `ConnectionInterface`.
-   Added methods:
-   `beginTransaction()`
-   `commit()`
-   `rollBack()`
-   `transaction(callable $callback)`
-   The `transaction()` method allows performing multiple operations within a single transaction with automatic `commit` or `rollback` on exception.

## [0.3.0] - 2025-11-21

### Added

-   Implemented support for `INSERT`, `UPDATE`, and `DELETE` in `QueryBuilder`.
-   Added new methods:
-   `insert(array $values)` — insert a row.
-   `insertGetId(array $values)` — insert a row and return its ID.
-   `update(array $values)` — update rows based on conditions.
-   `delete()` — delete rows.
-   Implemented SQL compilers:
-   `compileInsert()`
-   `compileUpdate()`
-   `compileDelete()`
-   Added internal binding extensions, including WHERE, ORDER BY, and LIMIT/OFFSET handling for updates and deletes.

## [0.2.0] – 2025-11-20

### Added

-   Implemented basic `QueryBuilder`:
-   `table()` for creating a new builder
-   `select()` for specifying selectable columns
-   `where()` / `orWhere()` for conditions
-   `orderBy()` for sorting
-   `limit()` and `offset()` for pagination
-   `get()` and `first()` for executing SELECT queries
-   `toSql()` and `getBindings()` for debugging
-   Added the `table()` method to `Connection` and `ConnectionInterface`.
-   Support for building SQL with placeholders and automatic bindings.

## [0.1.0] – 2025-11-19

### Added

-   Basic architecture of the `codemonster-ru/database` package.
-   Support for connecting to MySQL via PDO.
-   `Connection` class with methods:
-   `select()`
-   `selectOne()`
-   `insert()`
-   `update()`
-   `delete()`
-   `statement()`
-   Implemented `DatabaseManager` with support for multiple connections.
-   `QueryException` exception for PDO errors.
-   `ConnectionInterface` interface.
-   Prepared the structure for the future QueryBuilder (`Query/QueryBuilder.php`).
