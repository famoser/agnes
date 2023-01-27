<?php

namespace Agnes\Services;

use Agnes\Models\Connection\Connection;
use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\Configuration\Server;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

class InstanceService
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var InstallationService
     */
    private $installationService;

    /**
     * @var Instance[]|null
     */
    private $instancesCache = null;

    /**
     * @var Filter|null
     */
    private $instancesCacheFilter = null;

    /**
     * InstallationService constructor.
     */
    public function __construct(StyleInterface $io, ConfigurationService $configurationService, InstallationService $installationService)
    {
        $this->io = $io;
        $this->configurationService = $configurationService;
        $this->installationService = $installationService;
    }

    /**
     * @return Instance[]
     *
     * @throws Exception
     */
    public function getInstancesByFilter(?Filter $filter): array
    {
        if ($this->instancesCache && $this->instancesCacheFilter === $filter) {
            return $this->instancesCache;
        }

        $this->instancesCache = $this->loadInstances($filter);
        return $this->instancesCache;
    }

    /**
     * @return Instance[]
     *
     * @throws Exception
     */
    private function loadInstances(?Filter $filter): array
    {
        $servers = $this->configurationService->getServers();

        $instances = [];
        foreach ($servers as $server) {
            foreach ($server->getEnvironments() as $environment) {
                foreach ($environment->getStages() as $stage) {
                    if (!$filter->matches($server->getName(), $environment->getName(), $stage)) {
                        continue;
                    }

                    $this->io->text('loading  ' . $server->getName() . ":" . $environment->getName() . ":" . $stage);
                    $instances[] = $this->createInstance($server->getConnection(), $server->getPath(), $server, $environment->getName(), $stage);
                }
            }
        }

        return $instances;
    }

    /**
     * @throws Exception
     */
    private function createInstance(Connection $connection, string $path, Server $server, string $environment, string $stage): Instance
    {
        $instance = new Instance($connection, $path, $server->getName(), $server->getKeepInstallations(), $environment, $stage);

        $installations = $this->installationService->loadInstallations($instance);
        if (count($installations) > 0) {
            $this->io->text('loaded ' . count($installations) . ' installations of ' . $server->getName() . ':' . $environment . ':' . $stage);

            $symlink = $instance->getCurrentSymlink();
            $symlinkExists = $instance->getConnection()->checkSymlinkExists($symlink);
            $currentFolder = $symlinkExists ? $instance->getConnection()->readSymlink($symlink) : null;
            foreach ($installations as $installation) {
                $instance->addInstallation($installation);
                if ($installation->getFolder() === $currentFolder) {
                    $instance->setCurrentInstallation($installation);
                }
            }
        } else {
            $this->io->text('no installations yet at ' . $server->getName() . ':' . $environment . ':' . $stage . '.');
        }

        return $instance;
    }

    /**
     * @throws Exception
     */
    public function switchInstallation(Instance $instance, Installation $target): void
    {
        $currentSymlink = $instance->getCurrentSymlink();
        $connection = $instance->getConnection();

        // create new symlink
        $tempCurrentSymlink = $currentSymlink . '_';
        $connection->createSymlink($tempCurrentSymlink, $target->getFolder());

        // take old offline
        $old = $instance->getCurrentInstallation();
        if (null !== $old) {
            $this->installationService->isTakenOffline($instance, $old);
        }

        // switch
        $connection->replaceSymlink($tempCurrentSymlink, $currentSymlink);
        $instance->setCurrentInstallation($target);

        // take new online
        $this->installationService->wasTakenOnline($instance, $target);
    }

    /**
     * @throws Exception
     */
    public function removeOldInstallations(Instance $instance): void
    {
        $onlineNumber = $instance->getCurrentInstallation()->getNumber();
        /** @var Installation[] $oldInstallations */
        $oldInstallations = [];
        foreach ($instance->getInstallations() as $installation) {
            if ($installation->getNumber() < $onlineNumber) {
                $oldInstallations[$installation->getNumber()] = $installation;
            }
        }

        ksort($oldInstallations);

        // remove excess releases
        $installationsToDelete = count($oldInstallations) - $instance->getKeepInstallations();
        if (0 === $installationsToDelete) {
            return;
        }

        foreach ($oldInstallations as $installation) {
            if ($installationsToDelete-- <= 0) {
                break;
            }

            $instance->getConnection()->removeFolder($installation->getFolder());
            $this->io->text('removed installation ' . $installation->getFolder());
        }
    }

    /**
     * @return Instance[]
     *
     * @throws Exception
     */
    public function getInstancesBySpecification(string $target): array
    {
        $filter = Filter::createFromInstanceSpecification($target);

        return $this->getInstancesByFilter($filter);
    }
}
