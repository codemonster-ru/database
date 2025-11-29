<?php

namespace Codemonster\Database\CLI\Commands;

use Codemonster\Database\CLI\CommandInterface;
use Codemonster\Database\Migrations\Migrator;

class StatusCommand implements CommandInterface
{
    protected Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function signature(): string
    {
        return 'migrate:status';
    }

    public function description(): string
    {
        return 'Show migration status';
    }

    public function handle(array $arguments): int
    {
        $status = $this->migrator->getStatus();

        if (empty($status)) {
            fwrite(STDOUT, "No migrations found.\n");

            return 0;
        }

        fwrite(STDOUT, sprintf("%-8s  %-60s\n", 'Batch', 'Migration'));
        fwrite(STDOUT, str_repeat('-', 72) . "\n");

        foreach ($status as $item) {
            $batch = $item['batch'] === null ? '-' : (string) $item['batch'];

            fwrite(STDOUT, sprintf(
                "%-8s  %-60s\n",
                $batch,
                $item['migration']
            ));
        }

        return 0;
    }
}
