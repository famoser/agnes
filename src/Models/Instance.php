<?php

namespace Agnes\Models;

use Agnes\Models\Connections\Connection;
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
    private $installations = [];

    /**
     * @var Installation|null
     */
    private $currentInstallation;

    /**
     * Instance constructor.
     *
     * @param Installation[] $installations
     */
    public function __construct(Server $server, Environment $environment, string $stage, array $installations, ?Installation $currentInstallation)
    {
        $this->server = $server;
        $this->environment = $environment;
        $this->stage = $stage;

        foreach ($installations as $installation) {
            $this->installations[$installation->getNumber()] = $installation;
        }
        ksort($this->installations);

        $this->currentInstallation = $currentInstallation;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getConnection(): Connection
    {
        return $this->server->getConnection();
    }

    public function getServerName(): string
    {
        return $this->server->getName();
    }

    public function getEnvironmentName(): string
    {
        return $this->environment->getName();
    }

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

    public function getCurrentInstallation(): ?Installation
    {
        return $this->currentInstallation;
    }

    public function setCurrentInstallation(Installation $target)
    {
        $this->currentInstallation = $target;
    }

    /**
     * @return bool
     */
    public function equals(Instance $other)
    {
        if ($this === $other) {
            return true;
        }

        if ($this->getServerName() === $other->getServerName() &&
            $this->getEnvironmentName() === $other->getEnvironmentName() &&
            $this->getStage() === $other->getStage()) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function describe()
    {
        return $this->getServerName().':'.$this->getEnvironmentName().':'.$this->getStage();
    }
}
