<?php

namespace Agnes\Models;

use Agnes\Models\Connection\Connection;
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
    public function __construct(Server $server, Environment $environment, string $stage)
    {
        $this->server = $server;
        $this->environment = $environment;
        $this->stage = $stage;
    }

    public function addInstallation(Installation $installation)
    {
        $this->installations[$installation->getNumber()] = $installation;
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

    public function getInstallationsFolder(): string
    {
        return $this->getInstanceFolder().DIRECTORY_SEPARATOR.'installations';
    }

    public function getCurrentSymlink(): string
    {
        return $this->getInstanceFolder().DIRECTORY_SEPARATOR.'current';
    }

    public function getSharedFolder(): string
    {
        return $this->getInstanceFolder().DIRECTORY_SEPARATOR.'shared';
    }

    private function getInstanceFolder(): string
    {
        return $this->server->getPath().DIRECTORY_SEPARATOR.$this->environment->getName().DIRECTORY_SEPARATOR.$this->stage;
    }

    /**
     * @return string
     */
    public function describe()
    {
        return $this->getServerName().':'.$this->getEnvironmentName().':'.$this->getStage();
    }
}
