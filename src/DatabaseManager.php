<?php

namespace Codemonster\Database;

use Codemonster\Database\Contracts\ConnectionInterface;

class DatabaseManager
{
    protected array $connections = [];
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connection(?string $name = null): ConnectionInterface
    {
        $name ??= $this->config['default'];

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = new Connection(
                $this->config['connections'][$name]
            );
        }

        return $this->connections[$name];
    }
}
