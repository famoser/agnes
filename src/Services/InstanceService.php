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
     * @var FileService
     */
    private $fileService;

    /**
     * @var Instance[]|null
     */
    private $instancesCache = null;

    /**
     * InstallationService constructor.
     * @param ConfigurationService $configurationService
     * @param FileService $fileService
     */
    public function __construct(ConfigurationService $configurationService, FileService $fileService)
    {
        $this->configurationService = $configurationService;
        $this->fileService = $fileService;
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
        $folders = $connection->getFolders($releasesFolder, $this->fileService);

        $installations = [];

        foreach ($folders as $folder) {
            $installationPath = $releasesFolder . DIRECTORY_SEPARATOR . $folder;
            $agnesFilePath = $installationPath . DIRECTORY_SEPARATOR . ".agnes";

            if ($connection->checkFileExists($agnesFilePath, $this->fileService)) {
                $metaJson = $connection->readFile($agnesFilePath, $this->fileService);
                $meta = json_decode($metaJson);
                $installationDateTime = isset($meta["installationAt"]) ? new \DateTime($meta["installationAt"]) : null;
                $release = new Release($meta["release"]["name"], $meta["release"]["commitish"]);

                $installations[] = new Installation($installationPath, $installationDateTime, $release);
            }
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
                        $releasesFolder = $server->getConnection()->getWorkingFolder() . DIRECTORY_SEPARATOR . $environment->getName() . DIRECTORY_SEPARATOR . $stage . DIRECTORY_SEPARATOR . "releases";
                        $installations = $this->loadInstallations($server->getConnection(), $releasesFolder);

                        $instances[] = new Instance($server->getConnection(), $server->getName(), $environment->getName(), $stage, $installations);
                    }
                }
            }
        }

        return $instances;
    }
}