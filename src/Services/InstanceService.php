<?php

namespace Agnes\Services;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Models\Setup;
use Agnes\Services\Configuration\Environment;
use Agnes\Services\Configuration\Server;
use Exception;

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
     */
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @return Instance[]
     *
     * @throws Exception
     */
    public function getInstancesByFilter(?Filter $filter)
    {
        if (null === $this->instancesCache) {
            $this->instancesCache = $this->loadInstances();
        }

        if (null === $filter) {
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
     * @return Instance[]
     *
     * @throws Exception
     */
    private function loadInstances()
    {
        $servers = $this->configurationService->getServers();

        $instances = [];
        foreach ($servers as $server) {
            foreach ($server->getEnvironments() as $environment) {
                foreach ($environment->getStages() as $stage) {
                    $releasesFolder = $this->getInstallationsFolder($server, $environment, $stage);

                    $installations = $this->loadInstallations($server->getConnection(), $releasesFolder);
                    $currentInstallation = $this->getCurrentInstallation($server, $environment, $stage, $installations);
                    $instances[] = new Instance($server, $environment, $stage, $installations, $currentInstallation);
                }
            }
        }

        return $instances;
    }

    /**
     * @param Installation[] $installations
     *
     * @return mixed|null
     *
     * @throws Exception
     */
    private function getCurrentInstallation(Server $server, Environment $environment, string $stage, array $installations)
    {
        $currentReleaseFolder = $this->getCurrentSymlink($server, $environment, $stage);
        if (!$server->getConnection()->checkFolderExists($currentReleaseFolder)) {
            return null;
        }

        $currentInstallation = $this->getInstallationFromPath($server->getConnection(), $currentReleaseFolder);
        if (null === $currentInstallation) {
            return null;
        }

        foreach ($installations as $installation) {
            if ($installation->getNumber() === $currentInstallation->getNumber()) {
                return $installation;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function switchInstallation(Instance $instance, Installation $target): void
    {
        $currentSymlink = $this->getCurrentReleaseSymlinkPath($instance);
        $connection = $instance->getConnection();

        // create new symlink
        $tempCurrentSymlink = $currentSymlink.'_';
        $connection->createSymlink($tempCurrentSymlink, $target->getFolder());

        // take old offline
        $old = $instance->getCurrentInstallation();
        if (null !== $old) {
            $old->stopOnlinePeriod();
            $this->saveInstallation($connection, $old);
        }

        // switch
        $connection->replaceSymlink($tempCurrentSymlink, $currentSymlink);
        $instance->setCurrentInstallation($target);

        // take new online
        $target->startOnlinePeriod();
        $this->saveInstallation($connection, $target);
    }

    /**
     * @return Installation[]
     *
     * @throws Exception
     */
    private function loadInstallations(Connection $connection, string $releasesFolder)
    {
        $folders = $connection->getFolders($releasesFolder);

        $installations = [];

        foreach ($folders as $folder) {
            $installation = $this->getInstallationFromPath($connection, $folder);
            if (null !== $installation) {
                $installations[] = $installation;
            }
        }

        return $installations;
    }

    public function createInstallation(Instance $target, Setup $setup)
    {
        $identification = $setup->getIdentification();
        $installationFolder = $this->getInstallationsFolder($target->getServer(), $target->getEnvironment(), $target->getStage()).DIRECTORY_SEPARATOR.$identification;
        if ($target->getConnection()->checkFolderExists($installationFolder)) {
            $duplicationCounter = 1;
            while ($target->getConnection()->checkFolderExists($installationFolder).'-'.$duplicationCounter) {
                ++$duplicationCounter;
            }
            $installationFolder .= '-'.$duplicationCounter;
        }

        $maxNumber = 0;
        foreach ($target->getInstallations() as $installation) {
            $maxNumber = max((int) $installation->getNumber(), $maxNumber);
        }
        ++$maxNumber;

        return new Installation($installationFolder, $maxNumber, $setup);
    }

    /**
     * @throws Exception
     */
    private function getInstallationFromPath(Connection $connection, string $installationPath): ?Installation
    {
        $agnesFilePath = $this->getAgnesMetaFilePath($installationPath);

        if (!$connection->checkFileExists($agnesFilePath)) {
            return null;
        }

        $metaJson = $connection->readFile($agnesFilePath);
        $array = json_decode($metaJson, true);

        return Installation::fromArray($installationPath, $array);
    }

    private function saveInstallation(Connection $connection, Installation $installation)
    {
        $metaJson = json_encode($installation->toArray(), JSON_PRETTY_PRINT);
        $agnesFilePath = $this->getAgnesMetaFilePath($installation->getFolder());
        $connection->writeFile($agnesFilePath, $metaJson);
    }

    /**
     * @return string
     */
    public function getCurrentReleaseSymlinkPath(Instance $target)
    {
        return $this->getCurrentSymlink($target->getServer(), $target->getEnvironment(), $target->getStage());
    }

    /**
     * @return string
     */
    public function getSharedPath(Instance $target)
    {
        return $this->getSharedFolder($target->getServer(), $target->getEnvironment(), $target->getStage());
    }

    private function getStageFolder(Configuration\Server $server, Configuration\Environment $environment, string $stage): string
    {
        return $server->getPath().DIRECTORY_SEPARATOR.$environment->getName().DIRECTORY_SEPARATOR.$stage;
    }

    private function getInstallationsFolder(Configuration\Server $server, Configuration\Environment $environment, string $stage): string
    {
        return $this->getStageFolder($server, $environment, $stage).DIRECTORY_SEPARATOR.'installations';
    }

    private function getCurrentSymlink(Configuration\Server $server, Configuration\Environment $environment, string $stage): string
    {
        return $this->getStageFolder($server, $environment, $stage).DIRECTORY_SEPARATOR.'current';
    }

    private function getSharedFolder(Configuration\Server $server, Configuration\Environment $environment, string $stage): string
    {
        return $this->getStageFolder($server, $environment, $stage).DIRECTORY_SEPARATOR.'shared';
    }

    private function getAgnesMetaFilePath(string $installationPath): string
    {
        return $installationPath.DIRECTORY_SEPARATOR.'.agnes';
    }
}
