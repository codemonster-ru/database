# Changelog

All significant changes to this project will be documented in this file.

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
