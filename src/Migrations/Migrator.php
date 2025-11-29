<?php

namespace Codemonster\Database\Migrations;

use Codemonster\Database\Contracts\ConnectionInterface;

class Migrator
{
    protected MigrationRepository $repository;

    protected ConnectionInterface $connection;

    protected MigrationPathResolver $paths;

    public function __construct(
        MigrationRepository $repository,
        ConnectionInterface $connection,
        MigrationPathResolver $paths
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->paths = $paths;
    }

    /**
     * Run all pending migrations.
     *
     * @return string[] List of executed migration names
     */
    public function migrate(): array
    {
        $files = $this->getMigrationFiles();
        $ran = $this->repository->getRan();
        $ranNames = array_column($ran, 'migration');

        $pending = array_diff(array_keys($files), $ranNames);

        if (empty($pending)) {
            return [];
        }

        sort($pending);

        $batch = $this->repository->getLastBatchNumber() + 1;
        $executed = [];

        foreach ($pending as $migration) {
            $file = $files[$migration];

            $instance = $this->resolveMigration($file);

            $instance->up();

            $this->repository->log($migration, $batch);

            $executed[] = $migration;
        }

        return $executed;
    }

    /**
     * Rollback last batch (or part of it).
     *
     * @return string[] List of rolled back migration names
     */
    public function rollback(int $steps = 1): array
    {
        $lastBatch = $this->repository->getLastBatchNumber();

        if ($lastBatch === 0) {
            return [];
        }

        $migrations = $this->repository->getMigrationsByBatch($lastBatch);

        if ($steps > 0) {
            $migrations = array_slice($migrations, 0, $steps);
        }

        $files = $this->getMigrationFiles();
        $rolled = [];

        foreach ($migrations as $migration) {
            if (!isset($files[$migration])) {
                continue;
            }

            $file = $files[$migration];
            $instance = $this->resolveMigration($file);

            $instance->down();

            $this->repository->delete($migration);

            $rolled[] = $migration;
        }

        return $rolled;
    }

    /**
     * Get status of all migrations.
     *
     * @return array<int, array{migration:string,batch:?int}>
     */
    public function getStatus(): array
    {
        $files = $this->getMigrationFiles();
        $names = array_keys($files);

        return $this->repository->getStatus($names);
    }

    /**
     * @return array<string,string> [migrationName => filePath]
     */
    public function getMigrationFiles(): array
    {
        $files = [];

        foreach ($this->paths->getPaths() as $path) {
            foreach (glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                $name = basename($file, '.php');
                $files[$name] = $file;
            }
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    /**
     * Load migration instance from file.
     */
    protected function resolveMigration(string $file): Migration
    {
        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new \RuntimeException("Migration file [$file] must return instance of " . Migration::class);
        }

        return $migration;
    }
}
