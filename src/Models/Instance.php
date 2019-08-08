<?php


namespace Agnes\Models\Tasks;


use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;

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
     * @var Installation[]
     */
    private $installations;

    /**
     * @var Installation[]
     */
    private $currentInstallation;

    /**
     * Instance constructor.
     * @param Connection $connection
     * @param string $server
     * @param string $environment
     * @param string $stage
     * @param array $installations
     * @param Installation|null $currentInstallation
     */
    public function __construct(Connection $connection, string $server, string $environment, string $stage, array $installations, ?Installation $currentInstallation)
    {
        $this->connection = $connection;
        $this->server = $server;
        $this->environment = $environment;
        $this->stage = $stage;
        $this->installations = $installations;
        $this->currentInstallation = $currentInstallation;
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

    /**
     * @return Installation[]
     */
    public function getInstallations(): array
    {
        return $this->installations;
    }

    /**
     * @return Installation[]
     */
    public function getCurrentInstallation(): array
    {
        return $this->currentInstallation;
    }
}