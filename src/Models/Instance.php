<?php


namespace Agnes\Models;


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
    private $installations = [];

    /**
     * @var Installation
     */
    private $currentInstallation;

    /**
     * Instance constructor.
     * @param Server $server
     * @param Environment $environment
     * @param string $stage
     * @param Installation[] $installations
     * @param Installation|null $currentInstallation
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

    /**
     * @param string $releaseName
     * @return bool
     */
    public function isCurrentInstallation(string $releaseName): bool
    {
        return $this->currentInstallation->getRelease()->getName() === $releaseName;
    }

    /**
     * @param string $releaseName
     * @return Installation|null
     */
    public function getInstallation(string $releaseName): ?Installation
    {
        foreach ($this->installations as $installation) {
            if ($installation->getRelease()->getName() === $releaseName) {
                return $installation;
            }
        }

        return null;
    }

    /**
     * @return int
     */
    public function getKeepReleases()
    {
        return $this->server->getKeepReleases();
    }

    /**
     * get previous installation
     */
    public function getPreviousInstallation(): ?Installation
    {
        $currentReleaseNumber = $this->getCurrentInstallation()->getNumber();

        /** @var Installation|null $upperBoundRelease */
        $upperBoundRelease = null;

        foreach ($this->getInstallations() as $installation) {
            if ($installation->getNumber() !== null &&
                $installation->getNumber() < $currentReleaseNumber &&
                ($upperBoundRelease === null || $upperBoundRelease->getNumber() < $installation->getNumber())) {
                $upperBoundRelease = $installation;
            }
        }

        return $upperBoundRelease;
    }
}