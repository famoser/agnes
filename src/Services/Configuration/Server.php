<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Connection\Connection;

class Server
{
    private string $name;

    private Connection $connection;

    private string $path;

    private int $keepInstallations;

    /**
     * @var Environment[]
     */
    private array $environments;

    /**
     * Server constructor.
     *
     * @param Environment[] $environments
     */
    public function __construct(string $name, Connection $connection, string $path, int $keepInstallations, array $scriptOverrides, array $environments)
    {
        $this->name = $name;
        $this->connection = $connection;
        $this->path = $path;
        $this->keepInstallations = $keepInstallations;
        $this->environments = $environments;

        $connection->setScriptOverrides($scriptOverrides);
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
