<?php


namespace Agnes\Services;


use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;
use Agnes\Models\Tasks\Filter;
use Agnes\Models\Tasks\Instance;
use Agnes\Release\Release;

class InstanceService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var Instance[]|null
     */
    private $instancesCache = null;

    /**
     * InstallationService constructor.
     * @param ConfigurationService $configurationService
     */
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @param Filter|null $filter
     * @return Instance[]
     * @throws \Exception
     */
    public function getInstances(?Filter $filter)
    {
        if ($this->instancesCache === null) {
            $this->instancesCache = $this->loadInstances();
        }

        if ($filter === null) {
            return $this->instancesCache;
        }

        /** @var Instance[] $filteredInstallations */
        $filteredInstallations = [];
        foreach ($this->instancesCache as $installation) {
            if ($filter->instanceMatches($installation)) {
                $filteredInstallations[] = $installation;
            }
        }

        return $filteredInstallations;
    }

    /**
     * @param Connection $connection
     * @param string $releasesFolder
     * @return array
     * @throws \Exception
     */
    public function loadInstallations(Connection $connection, string $releasesFolder)
    {
        $folders = $connection->getFolders($releasesFolder);

        $installations = [];

        foreach ($folders as $folder) {
            $installationPath = $releasesFolder . DIRECTORY_SEPARATOR . $folder;
            $installations[] = $this->getInstallationAtPath($connection, $installationPath);
        }

        return $installations;
    }

    /**
     * @return Instance[]
     * @throws \Exception
     */
    private function loadInstances()
    {
        $servers = $this->configurationService->getServers();

        $instances = [];
        foreach ($servers as $server) {
            foreach ($server->getEnvironments() as $environment) {
                foreach ($environment->getStages() as $stages) {
                    foreach ($stages as $stage) {
                        $stageFolder = $server->getConnection()->getWorkingFolder() . DIRECTORY_SEPARATOR . $environment->getName() . DIRECTORY_SEPARATOR . $stage;
                        $releasesFolder = $stageFolder . DIRECTORY_SEPARATOR . "releases";
                        $installations = $this->loadInstallations($server->getConnection(), $releasesFolder);

                        $currentReleaseFolder = $stageFolder . DIRECTORY_SEPARATOR . "current";
                        $currentInstallation = null;
                        if ($server->getConnection()->checkFolderExists($currentReleaseFolder)) {
                            $currentInstallation = $this->getInstallationAtPath($server->getConnection(), $currentReleaseFolder);
                        }

                        $instances[] = new Instance($server->getConnection(), $server->getName(), $environment->getName(), $stage, $installations, $currentInstallation);
                    }
                }
            }
        }

        return $instances;
    }

    /**
     * @param Connection $connection
     * @param string $installationPath
     * @return Installation
     * @throws \Exception
     */
    private function getInstallationAtPath(Connection $connection, string $installationPath): Installation
    {
        $agnesFilePath = $installationPath . DIRECTORY_SEPARATOR . ".agnes";

        if (!$connection->checkFileExists($agnesFilePath)) {
            return new Installation($installationPath);
        }

        $metaJson = $connection->readFile($agnesFilePath);
        $meta = json_decode($metaJson);
        $release = new Release($meta["release"]["name"], $meta["release"]["commitish"]);
        $installedAt = isset($meta["installedAt"]) ? new \DateTime($meta["installedAt"]) : null;
        $releasedAt = isset($meta["releasedAt"]) ? new \DateTime($meta["releasedAt"]) : null;

        return new Installation($installationPath, $release, $installedAt, $releasedAt);
    }
}