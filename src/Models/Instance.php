<?php

namespace Agnes\Models;

use Agnes\Models\Connection\Connection;

class Instance
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
     * @var int
     */
    private $keepInstallations;

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
    private $installations = [];

    /**
     * @var Installation|null
     */
    private $currentInstallation;

    /**
     * Instance constructor.
     */
    public function __construct(Connection $connection, string $path, string $server, int $keepInstallations, string $environment, string $stage)
    {
        $this->connection = $connection;
        $this->path = $path;
        $this->server = $server;
        $this->keepInstallations = $keepInstallations;
        $this->environment = $environment;
        $this->stage = $stage;
    }

    public function addInstallation(Installation $installation): void
    {
        $this->installations[$installation->getNumber()] = $installation;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getServerName(): string
    {
        return $this->server;
    }

    public function getKeepInstallations(): int
    {
        return $this->keepInstallations;
    }

    public function getEnvironmentName(): string
    {
        return $this->environment;
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

    public function setCurrentInstallation(Installation $target): void
    {
        $this->currentInstallation = $target;
    }

    public function equals(Instance $other): bool
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
        return $this->path.DIRECTORY_SEPARATOR.$this->environment.DIRECTORY_SEPARATOR.$this->stage;
    }

    public function describe(): string
    {
        return $this->server.':'.$this->environment.':'.$this->stage;
    }
}
