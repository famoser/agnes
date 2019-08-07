<?php


namespace Agnes\Services\Configuration;


use Agnes\Models\Connections\Connection;
use Agnes\Release\Release;

class Installation
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var
     */
    private $releaseMeta;

    /**
     * @var Release
     */
    private $release;

    /**
     * Installation constructor.
     * @param Connection $connection
     * @param string $path
     * @param string $server
     * @param string $environment
     * @param string $stage
     */
    public function __construct(string $path)
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