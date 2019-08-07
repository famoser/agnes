<?php


namespace Agnes\Models\Tasks;


use Agnes\Models\Connections\Connection;

class Instance
{
    /**
     * @var Connection
     */
    private $connection;

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
     * Instance constructor.
     * @param Connection $connection
     * @param string $server
     * @param string $environment
     * @param string $stage
     */
    public function __construct(Connection $connection, string $server, string $environment, string $stage)
    {
        $this->connection = $connection;
        $this->server = $server;
        $this->environment = $environment;
        $this->stage = $stage;
    }

    /**
     * @return string
     */
    public function getInstallationsFolder()
    {
        return $this->environment . DIRECTORY_SEPARATOR . $this->stage;
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