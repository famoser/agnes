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
     * @param string $name
     * @param Connection $connection
     * @param string $path
     * @param int $keepReleases
     * @param array $scriptOverrides
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

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return int
     */
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

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}