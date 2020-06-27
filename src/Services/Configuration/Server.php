<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Connections\Connection;

class Server
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $keepReleases;

    /**
     * @var Environment[]
     */
    private $environments;

    /**
     * Server constructor.
     *
     * @param Environment[] $environments
     */
    public function __construct(string $name, Connection $connection, string $path, int $keepReleases, array $scriptOverrides, array $environments)
    {
        $this->name = $name;
        $this->connection = $connection;
        $this->path = $path;
        $this->keepReleases = $keepReleases;
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

    public function getKeepReleases(): int
    {
        return $this->keepReleases;
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
