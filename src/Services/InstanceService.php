<?php


namespace Agnes\Services;


use Agnes\Actions\Release;
use Agnes\Models\Connections\Connection;
use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Models\OnlinePeriod;
use Agnes\Services\Configuration\Environment;
use Agnes\Services\Configuration\Server;
use DateTime;
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
     * @param ConfigurationService $configurationService
     */
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @param string $target
     * @return Instance[]
     * @throws Exception
     */
    public function getInstancesFromInstanceSpecification(string $target)
    {
        $entries = explode(":", $target);

        $parseToArray = function ($entry) {
            return $entry !== "*" ? explode(",", $entry) : [];
        };

        $servers = $parseToArray($entries[0]);
        $environments = $parseToArray($entries[1]);
        $stages = $parseToArray($entries[2]);
        $filter = new Filter($servers, $environments, $stages);

        return $this->getInstancesByFilter($filter);
    }

    /**
     * @param Filter|null $filter
     * @return Instance[]
     * @throws Exception
     */
    public function getInstancesByFilter(?Filter $filter)
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
     * @return Instance[]
     * @throws Exception
     */
    private function loadInstances()
    {
        $servers = $this->configurationService->getServers();

        $instances = [];
        foreach ($servers as $server) {
            foreach ($server->getEnvironments() as $environment) {
                foreach ($environment->getStages() as $stage) {
                    $releasesFolder = $this->getReleasesFolder($server, $environment, $stage);

                    $installations = $this->loadInstallations($server->getConnection(), $releasesFolder);
                    $currentInstallation = $this->getCurrentInstallation($server, $environment, $stage, $installations);
                    $instances[] = new Instance($server, $environment, $stage, $installations, $currentInstallation);
                }
            }
        }

        return $instances;
    }

    /**
     * @param Server $server
     * @param Environment $environment
     * @param string $stage
     * @param Installation[] $installations
     * @return mixed|null
     * @throws Exception
     */
    private function getCurrentInstallation(Server $server, Environment $environment, string $stage, array $installations)
    {
        $currentReleaseFolder = $this->getCurrentReleaseSymlink($server, $environment, $stage);
        if (!$server->getConnection()->checkFolderExists($currentReleaseFolder)) {
            return null;
        }

        $currentInstallation = $this->getInstallationFromPath($server->getConnection(), $currentReleaseFolder);
        if ($currentInstallation->getRelease() === null) {
            return null;
        }

        foreach ($installations as $installation) {
            if ($installation->getRelease() !== null && $installation->isSameReleaseName($currentInstallation->getRelease()->getName())) {
                return $installation;
            }
        }

        return null;
    }

    /**
     * @param Instance $target
     * @param Release $release
     * @throws Exception
     */
    public function switchRelease(Instance $target, Release $release): void
    {
        $currentSymlink = $this->getCurrentReleaseSymlinkPath($target);
        $targetFolder = $this->getReleasePath($target, $release);
        $connection = $target->getConnection();

        // create new symlink
        $tempCurrentSymlink = $currentSymlink . "_";
        $connection->createSymlink($tempCurrentSymlink, $targetFolder);

        // switch active release
        $this->onReleaseOffline($connection, $currentSymlink);
        $connection->moveFile($tempCurrentSymlink, $currentSymlink);
        $this->onReleaseOnline($connection, $targetFolder);
    }

    /**
     * @param Connection $connection
     * @param string $releasesFolder
     * @return Installation[]
     * @throws Exception
     */
    private function loadInstallations(Connection $connection, string $releasesFolder)
    {
        $folders = $connection->getFolders($releasesFolder);

        $installations = [];

        foreach ($folders as $folder) {
            $installations[] = $this->getInstallationFromPath($connection, $folder);
        }

        return $installations;
    }

    /**
     * @param Instance $instance
     * @param string $installationPath
     * @param Release $release
     * @throws Exception
     */
    public function onReleaseInstalled(Instance $instance, string $installationPath, Release $release)
    {
        $connection = $instance->getConnection();
        $maxReleaseNumber = 0;
        foreach ($instance->getInstallations() as $installation) {
            $maxReleaseNumber = max((int)$installation->getNumber(), $maxReleaseNumber);
        }

        $installation = $this->getInstallationFromPath($connection, $installationPath);
        $installation->setRelease($maxReleaseNumber + 1, $release);

        $this->saveInstallationToPath($connection, $installationPath, $installation);
    }

    /**
     * @param Connection $connection
     * @param string $installationPath
     * @throws Exception
     */
    public function onReleaseOnline(Connection $connection, string $installationPath)
    {
        $installation = $this->getInstallationFromPath($connection, $installationPath);
        $installation->takeOnline();

        $this->saveInstallationToPath($connection, $installationPath, $installation);
    }

    /**
     * @param Connection $connection
     * @param string $installationPath
     * @throws Exception
     */
    public function onReleaseOffline(Connection $connection, string $installationPath)
    {
        $installation = $this->getInstallationFromPath($connection, $installationPath);
        // no release at that path; hence lets not save
        if ($installation->getRelease() === null) {
            return;
        }

        $installation->takeOffline();

        $this->saveInstallationToPath($connection, $installationPath, $installation);
    }

    /**
     * @param Connection $connection
     * @param string $installationPath
     * @return Installation
     * @throws Exception
     */
    private function getInstallationFromPath(Connection $connection, string $installationPath): Installation
    {
        $agnesFilePath = $this->getAgnesMetaFilePath($installationPath);

        if (!$connection->checkFileExists($agnesFilePath)) {
            return new Installation($installationPath);
        }

        $metaJson = $connection->readFile($agnesFilePath);
        $meta = json_decode($metaJson);
        $number = $meta->number;
        $release = new Release($meta->release->name, $meta->release->commitish);

        $onlinePeriods = [];
        foreach ($meta->online_periods as $onlinePeriod) {
            $start = new DateTime($onlinePeriod->start);
            $end = $onlinePeriod->end !== null ? new DateTime($onlinePeriod->end) : null;
            $onlinePeriods[] = new OnlinePeriod($start, $end);
        }

        return new Installation($installationPath, $number, $release, $onlinePeriods);
    }

    /**
     * @param Connection $connection
     * @param string $installationPath
     * @param Installation $installation
     */
    private function saveInstallationToPath(Connection $connection, string $installationPath, Installation $installation)
    {
        $meta = [];
        $meta["number"] = $installation->getNumber();
        $meta["release"] = ["name" => $installation->getRelease()->getName(), "commitish" => $installation->getRelease()->getCommitish()];

        $onlinePeriods = [];
        foreach ($installation->getOnlinePeriods() as $onlinePeriod) {
            $onlinePeriods[] = [
                "start" => $onlinePeriod->getStart()->format("c"),
                "end" => $onlinePeriod->getEnd() ? $onlinePeriod->getEnd()->format("c") : null
            ];
        }

        $meta["online_periods"] = $onlinePeriods;

        $metaJson = json_encode($meta, JSON_PRETTY_PRINT);
        $agnesFilePath = $this->getAgnesMetaFilePath($installationPath);
        $connection->writeFile($agnesFilePath, $metaJson);
    }

    /**
     * @param Instance $target
     * @param Release $release
     * @return string
     */
    public function getReleasePath(Instance $target, Release $release)
    {
        return $this->getReleaseFolder($target->getServer(), $target->getEnvironment(), $target->getStage(), $release);
    }

    /**
     * @param Instance $target
     * @return string
     */
    public function getCurrentReleaseSymlinkPath(Instance $target)
    {
        return $this->getCurrentReleaseSymlink($target->getServer(), $target->getEnvironment(), $target->getStage());
    }

    /**
     * @param Instance $target
     * @return string
     */
    public function getSharedPath(Instance $target)
    {
        return $this->getSharedFolder($target->getServer(), $target->getEnvironment(), $target->getStage());
    }

    /**
     * @param Configuration\Server $server
     * @param Configuration\Environment $environment
     * @param string $stage
     * @return string
     */
    private function getStageFolder(Configuration\Server $server, Configuration\Environment $environment, string $stage): string
    {
        return $server->getPath() . DIRECTORY_SEPARATOR . $environment->getName() . DIRECTORY_SEPARATOR . $stage;
    }

    /**
     * @param Configuration\Server $server
     * @param Configuration\Environment $environment
     * @param string $stage
     * @return string
     */
    private function getReleasesFolder(Configuration\Server $server, Configuration\Environment $environment, string $stage): string
    {
        return $this->getStageFolder($server, $environment, $stage) . DIRECTORY_SEPARATOR . "releases";
    }

    /**
     * @param Configuration\Server $server
     * @param Configuration\Environment $environment
     * @param string $stage
     * @return string
     */
    private function getCurrentReleaseSymlink(Configuration\Server $server, Configuration\Environment $environment, string $stage): string
    {
        return $this->getStageFolder($server, $environment, $stage) . DIRECTORY_SEPARATOR . "current";
    }

    /**
     * @param Configuration\Server $server
     * @param Configuration\Environment $environment
     * @param string $stage
     * @return string
     */
    private function getSharedFolder(Configuration\Server $server, Configuration\Environment $environment, string $stage): string
    {
        return $this->getStageFolder($server, $environment, $stage) . DIRECTORY_SEPARATOR . "shared";
    }

    /**
     * @param Configuration\Server $server
     * @param Configuration\Environment $environment
     * @param string $stage
     * @param Release $release
     * @return string
     */
    private function getReleaseFolder(Configuration\Server $server, Configuration\Environment $environment, string $stage, Release $release): string
    {
        return $this->getReleasesFolder($server, $environment, $stage) . DIRECTORY_SEPARATOR . $release->getName();
    }

    /**
     * @param string $installationPath
     * @return string
     */
    private function getAgnesMetaFilePath(string $installationPath): string
    {
        return $installationPath . DIRECTORY_SEPARATOR . ".agnes";
    }
}