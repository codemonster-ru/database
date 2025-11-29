<?php

namespace Codemonster\Database\CLI;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Migrations\Migrator;
use Codemonster\Database\CLI\Commands\MigrateCommand;
use Codemonster\Database\CLI\Commands\RollbackCommand;
use Codemonster\Database\CLI\Commands\StatusCommand;
use Codemonster\Database\CLI\Commands\MakeMigrationCommand;

class DatabaseCLIKernel
{
    protected CommandRegistry $commands;

    protected MigrationPathResolver $paths;

    protected Migrator $migrator;

    public function __construct(ConnectionInterface $connection, ?MigrationPathResolver $paths = null)
    {
        $this->paths = $paths ?? new MigrationPathResolver();

        if (empty($this->paths->getPaths())) {
            $this->paths->addPath(getcwd() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        }

        $repository = new MigrationRepository($connection);

        $this->migrator = new Migrator($repository, $connection, $this->paths);
        $this->commands = new CommandRegistry();

        $this->registerDefaultCommands();
    }

    protected function registerDefaultCommands(): void
    {
        $this->commands->register(new MigrateCommand($this->migrator));
        $this->commands->register(new RollbackCommand($this->migrator));
        $this->commands->register(new StatusCommand($this->migrator));
        $this->commands->register(new MakeMigrationCommand($this->paths));
    }

    public function handle(array $argv): int
    {
        return $this->commands->dispatch($argv);
    }

    public function getRegistry(): CommandRegistry
    {
        return $this->commands;
    }

    public function getPathResolver(): MigrationPathResolver
    {
        return $this->paths;
    }

    public function getMigrator(): Migrator
    {
        return $this->migrator;
    }
}
