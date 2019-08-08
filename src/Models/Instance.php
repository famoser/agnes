<?php


namespace Agnes\Models\Tasks;


use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;
use Agnes\Services\Configuration\Environment;
use Agnes\Services\Configuration\Server;

class Instance
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var Environment
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
     * @var Installation
     */
    private $currentInstallation;

    /**
     * Instance constructor.
     * @param Server $server
     * @param Environment $environment
     * @param string $stage
     * @param array $installations
     * @param Installation|null $currentInstallation
     */
    public function __construct(Server $server, Environment $environment, string $stage, array $installations, ?Installation $currentInstallation)
    {
        $this->server = $server;
        $this->environment = $environment;
        $this->stage = $stage;
        $this->installations = $installations;
        $this->currentInstallation = $currentInstallation;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->server->getConnection();
    }

    /**
     * @return string
     */
    public function getServerName(): string
    {
        return $this->server->getName();
    }

    /**
     * @return string
     */
    public function getEnvironmentName(): string
    {
        return $this->environment->getName();
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
     * @return Installation
     */
    public function getCurrentInstallation(): Installation
    {
        return $this->currentInstallation;
    }
}