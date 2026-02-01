<?php

namespace Agnes\Services;

use Agnes\Models\Connection\Connection;
use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\Configuration\Server;
use Symfony\Component\Console\Style\StyleInterface;

class InstanceService
{
    private StyleInterface $io;

    private ConfigurationService $configurationService;

    private InstallationService $installationService;

    /**
     * @var Instance[]|null
     */
    private ?array $instancesCache = null;

    /**
     * @var Filter|null
     */
    private $instancesCacheFilter;

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

                    $this->io->text('loading  ' . $server->getName() . ':' . $environment->getName() . ':' . $stage);
                    $instances[] = $this->createInstance($server->getConnection(), $server->getPath(), $server, $environment->getName(), $stage);
                }
            }
        }

        return $instances;
    }

    private function createInstance(Connection $connection, string $path, Server $server, string $environment, string $stage): Instance
    {
        $instance = new Instance($connection, $path, $server->getName(), $server->getKeepInstallations(), $environment, $stage);

        $installations = $this->installationService->loadInstallations($instance);
        if ($installations !== []) {
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
     */
    public function getInstancesBySpecification(string $target): array
    {
        $filter = Filter::createFromInstanceSpecification($target);

        return $this->getInstancesByFilter($filter);
    }
}
