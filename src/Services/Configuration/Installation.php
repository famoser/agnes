<?php


namespace Agnes\Services\Configuration;


use Agnes\Models\Connections\Connection;

class Installation
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $server;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $stage;

    /**
     * Installation constructor.
     * @param Connection $connection
     * @param string $path
     * @param string $server
     * @param string $environment
     * @param string $stage
     */
    public function __construct(Connection $connection, string $path, string $server, string $environment, string $stage)
    {
        $this->connection = $connection;
        $this->path = $path;
        $this->server = $server;
        $this->environment = $environment;
        $this->stage = $stage;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @return string
     */
    public function getStage(): string
    {
        return $this->stage;
    }
}