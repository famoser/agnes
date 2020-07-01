<?php

namespace Agnes\Actions;

use Agnes\Services\ConfigurationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class CopySharedAction extends AbstractAction
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * CopySharedService constructor.
     */
    public function __construct(PolicyService $policyService, ConfigurationService $configurationService, InstanceService $instanceService)
    {
        parent::__construct($policyService);

        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
    }

    /**
     * @return CopyShared[]
     *
     * @throws Exception
     */
    public function createMany(string $source, string $target, OutputInterface $output): array
    {
        $sourceInstances = $this->instanceService->getInstancesFromInstanceSpecification($source);
        if (0 === count($sourceInstances)) {
            $output->writeln('For source specification '.$source.' no matching instances were found.');

            return [];
        }

        $targetInstances = $this->instanceService->getInstancesFromInstanceSpecification($target);
        if (0 === count($sourceInstances)) {
            $output->writeln('For target specification '.$target.' no matching instances were found.');

            return [];
        }

        /** @var CopyShared[] $copyShareds */
        $copyShareds = [];
        foreach ($targetInstances as $targetInstance) {
            $matchingInstances = $targetInstance->getSameEnvironmentInstances($sourceInstances);
            if (1 === count($matchingInstances)) {
                $copyShareds[] = new CopyShared($matchingInstances[0], $targetInstance);
            }
        }

        return $copyShareds;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute().
     *
     * @param CopyShared $copyShared
     */
    protected function canProcessPayload($copyShared, OutputInterface $output): bool
    {
        if (!$copyShared instanceof CopyShared) {
            $output->writeln('Not a '.CopyShared::class);

            return false;
        }

        // technical limitation: only copy from same connection
        if (!$copyShared->getSource()->getConnection()->equals($copyShared->getTarget()->getConnection())) {
            $output->writeln('Cannot execute '.$copyShared->describe().': copy shared only possible within same connection.');

            return false;
        }

        // does not make sense to copy from itself
        if ($copyShared->getSource()->equals($copyShared->getTarget())) {
            $output->writeln('Cannot execute '.$copyShared->describe().': copy shared to itself does not make sense.');

            return false;
        }

        return true;
    }

    /**
     * @param CopyShared $copyShared
     *
     * @throws Exception
     */
    protected function doExecute($copyShared, OutputInterface $output)
    {
        $sourceSharedPath = $this->instanceService->getSharedPath($copyShared->getSource());
        $targetSharedPath = $this->instanceService->getSharedPath($copyShared->getTarget());
        $connection = $copyShared->getSource()->getConnection();

        $sharedFolders = $this->configurationService->getSharedFolders();
        foreach ($sharedFolders as $sharedFolder) {
            $sourceFolderPath = $sourceSharedPath.DIRECTORY_SEPARATOR.$sharedFolder;
            $targetFolderPath = $targetSharedPath.DIRECTORY_SEPARATOR.$sharedFolder;

            $output->writeln('copying folder '.$sharedFolder);
            $connection->copyFolderContent($sourceFolderPath, $targetFolderPath);
        }
    }
}
