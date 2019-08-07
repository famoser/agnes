<?php


namespace Agnes\Services;


use Agnes\Models\Tasks\Filter;
use Agnes\Models\Tasks\Instance;
use Agnes\Services\Configuration\Installation;

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
    private $installationsCache = null;

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
        if ($this->installationsCache === null) {
            $this->installationsCache = $this->loadInstances();
        }

        if ($filter === null) {
            return $this->installationsCache;
        }

        /** @var Instance[] $filteredInstallations */
        $filteredInstallations = [];
        foreach ($this->installationsCache as $installation) {
            if ($filter->instanceMatches($installation)) {
                $filteredInstallations[] = $installation;
            }
        }

        return $filteredInstallations;
    }

    public function loadInstallations(Instance $instance)
    {
        $installationsFolder = $instance->getConnection()->getWorkingFolder() . DIRECTORY_SEPARATOR . $instance->getInstallationsFolder() . DIRECTORY_SEPARATOR . "releases";
        $folders = $instance->getConnection()->getFolders($installationsFolder, $this->fileService);

        foreach ($folders as $folder) {
            $installation = new Installation($installationsFolder . DIRECTORY_SEPARATOR . $folder);
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function loadInstances()
    {
        $servers = $this->configurationService->getServers();

        $instances = [];
        foreach ($servers as $server) {
            foreach ($server->getEnvironments() as $environment) {
                foreach ($environment->getStages() as $stages) {
                    $instances[] = new Instance($server->getConnection(), $server->getName(), $environment->getName(), $stages);
                }
            }
        }

        return $instances;
    }
}