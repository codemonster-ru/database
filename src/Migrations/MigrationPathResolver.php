<?php

namespace Codemonster\Database\Migrations;

class MigrationPathResolver
{
    /**
     * @var string[]
     */
    protected array $paths = [];

    /**
     * Add migrations path (only if directory exists).
     */
    public function addPath(string $path): void
    {
        if (is_dir($path)) {
            $this->paths[] = rtrim($path, DIRECTORY_SEPARATOR);
        }
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
}
