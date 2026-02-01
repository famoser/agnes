<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Connection\Connection;

readonly class Server
{
    /**
     * @param Environment[] $environments
     */
    public function __construct(private string $name, private Connection $connection, private string $path, private int $keepInstallations, array $scriptOverrides, private array $environments)
    {
        $this->connection->setScriptOverrides($scriptOverrides);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getKeepInstallations(): int
    {
        return $this->keepInstallations;
    }

    /**
     * @return Environment[]
     */
    public function getEnvironments(): array
    {
        return $this->environments;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
