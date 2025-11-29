# Changelog

All significant changes to this project will be documented in this file.

## [0.7.0] – 2025-12-01

### Added

-   Major QueryBuilder upgrade:
    -   Nested where-groups using closures
    -   whereRaw(), orWhereRaw()
    -   selectRaw(), orderByRaw()
    -   whereIn / whereNotIn / whereBetween / whereNotBetween / whereNull / whereNotNull
    -   JOIN system (innerJoin, leftJoin, rightJoin, crossJoin)
    -   DISTINCT support
    -   GROUP BY, HAVING, havingRaw()
    -   Aggregates: count(), sum(), avg(), min(), max()
    -   exists(), doesntExist()
    -   value(), pluck(), forPage(), simplePaginate()
-   Full SQL compiler implementation for:
    -   joins
    -   nested groups
    -   raw expressions
    -   complex conditions
    -   between/not between
    -   group/having order & priority
-   Added `RawExpression` class to support raw SQL segments

### Changed

-   Reworked WHERE compiler (now supports full tree-based logic)
-   Improved compileSelect() order: joins → where → group → having → order → limit → offset
-   Normalized SQL generation for consistent MySQL output
-   select() now accepts both strings and array of strings
-   orderByRaw merged into unified ORDER BY compiler

### Fixed

-   DISTINCT, GROUP BY, HAVING now compiled correctly
-   join() and whereRaw() functionality works fully
-   BETWEEN and NOT BETWEEN handled properly
-   ORDER BY raw expressions no longer wrapped incorrectly

## [0.6.0] – 2025-11-30

### Added

-   Fully featured migration system:
    -   `Migration` base class
    -   `MigrationRepository` for tracking applied migrations
    -   `Migrator` with support for batches, rollback, and status
    -   `MigrationPathResolver` for flexible multi-path migration loading
-   Standalone CLI tooling:
    -   `vendor/bin/database migrate`
    -   `vendor/bin/database migrate:rollback`
    -   `vendor/bin/database migrate:status`
    -   `vendor/bin/database make:migration`
    -   CLI kernel (`DatabaseCLIKernel`) and command registry
-   Automatic CLI integration support for external frameworks (e.g. Codemonster Annabel)
-   Support for multiple migration directories (modules, packages, custom paths)
-   Improved Schema Builder:
    -   Extended column types (decimal, float, double, char, json, mediumText, longText, datetime, uuid, etc.)
    -   Foreign key creation inside CREATE TABLE and ALTER TABLE
    -   Index creation/removal during schema changes

### Changed

-   Standardized Grammar output: all schema operations now return arrays of statements
-   Improved `bin/database` for standalone usage and dynamic config loading
-   Updated README with full documentation for Query Builder, Schema Builder, migrations, and CLI usage

### Fixed

-   IDE warnings for undefined global helpers using safe stub declarations
-   Missing return values in CLI helper functions

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
