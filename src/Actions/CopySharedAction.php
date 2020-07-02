<?php

namespace Agnes\Actions;

use Agnes\Models\Filter;
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
    public function createMany(string $source, string $targetStage, OutputInterface $output): array
    {
        $filter = Filter::createFromInstanceSpecification($source);
        if (!$filter->filtersBySingleStage()) {
            $output->writeln('To avoid ambiguities, please specify a single source stage to copy from (hence your source should be of the form *:*:prod).');

            return [];
        }

        $sourceInstances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($sourceInstances)) {
            $output->writeln('For source specification '.$source.' no matching instances were found.');

            return [];
        }

        /** @var CopyShared[] $copyShareds */
        $copyShareds = [];
        foreach ($sourceInstances as $sourceInstance) {
            $targetFilter = new Filter([$sourceInstance->getServerName()], [$sourceInstance->getEnvironmentName()], [$targetStage]);
            $targetInstance = $this->instanceService->getInstancesByFilter($targetFilter);

            if (0 === count($targetInstance)) {
                $output->writeln('For instance '.$sourceInstance->describe().' no matching target instance was found.');
                continue;
            }

            $copyShareds[] = new CopyShared($sourceInstance, $targetInstance[0]);
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
        $sourceSharedPath = $copyShared->getSource()->getSharedFolder();
        $targetSharedPath = $copyShared->getTarget()->getSharedFolder();
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
