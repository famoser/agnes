<?php


namespace Agnes\Services;


use Agnes\Models\Tasks\Filter;
use Agnes\Models\Tasks\Instance;

class InstanceService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var Instance[]|null
     */
    private $installationsCache = null;

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
        $installationsFolder = $instance->getConnection()->getWorkingFolder() . DIRECTORY_SEPARATOR . $instance->getInstallationsFolder();
        $folders = $instance->getConnection()->getFolders($installationsFolder);
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