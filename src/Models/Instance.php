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
    public function getCurrentInstallation(): ?Installation
    {
        return $this->currentInstallation;
    }

    /**
     * @param string $releaseName
     * @return bool
     */
    public function isCurrentRelease(string $releaseName): bool
    {
        if ($this->getCurrentInstallation() === null) {
            return false;
        }

        return $this->getCurrentInstallation()->isSameReleaseName($releaseName);
    }

    /**
     * @param string $releaseName
     * @return Installation|null
     */
    public function getInstallation(string $releaseName): ?Installation
    {
        foreach ($this->installations as $installation) {
            if ($installation->isSameReleaseName($releaseName)) {
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
        if ($this->getCurrentInstallation() === null) {
            return null;
        }

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

    /**
     * @return string
     */
    public function getCurrentReleaseName()
    {
        if ($this->getCurrentInstallation() != null && $this->getCurrentInstallation()->getRelease() !== null) {
            return $this->getCurrentInstallation()->getRelease()->getName();
        }

        return null;
    }

    /**
     * @param Instance[] $instances
     * @return Instance[]
     */
    public function getSameEnvironmentInstances(array $instances)
    {
        /** @var Instance[] $matching */
        $matching = [];
        foreach ($instances as $instance) {
            if ($instance->getEnvironmentName() === $this->getEnvironmentName()) {
                $matching[] = $instance;
            }
        }

        return $matching;
    }

    /**
     * @param string|null $rollbackTo
     * @param string|null $rollbackFrom
     * @return Installation|null
     */
    public function getRollbackTarget(?string $rollbackTo, ?string $rollbackFrom): ?Installation
    {
        // ensure instance active
        if ($this->getCurrentInstallation() === null) {
            return null;
        }

        // ensure rollbackFrom is what is currently active
        if ($rollbackFrom !== null && !$this->isCurrentRelease($rollbackFrom)) {
            return null;
        }

        // if no rollback target specified, return the previous installation
        if ($rollbackTo === null) {
            return $this->getPreviousInstallation();
        }

        // ensure target is not same than current release
        if ($this->isCurrentRelease($rollbackTo)) {
            return null;
        }

        // find matching installation & ensure it is indeed a previous release
        $targetInstallation = $this->getInstallation($rollbackTo);
        if ($targetInstallation !== null && $targetInstallation->getNumber() < $this->getCurrentInstallation()->getNumber()) {
            return $targetInstallation;
        }

        return null;
    }
}