<?php

namespace Agnes\Actions;

use Agnes\Models\Filter;
use Agnes\Models\Instance;
use Agnes\Services\ConfigurationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

class CopySharedAction extends AbstractAction
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
     * @var InstanceService
     */
    private $instanceService;

    /**
     * CopySharedService constructor.
     */
    public function __construct(StyleInterface $io, PolicyService $policyService, ConfigurationService $configurationService, InstanceService $instanceService)
    {
        parent::__construct($policyService);

        $this->io = $io;
        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
    }

    /**
     * @throws Exception
     */
    public function createSingle(Instance $target, string $sourceStage, OutputInterface $output): ?CopyShared
    {
        return $this->constructCopyShared($output, $target, $sourceStage);
    }

    /**
     * @return CopyShared[]
     *
     * @throws Exception
     */
    public function createMany(string $target, string $sourceStage, OutputInterface $output): array
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $targetInstances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($targetInstances)) {
            $this->io->warning('For target specification '.$target.' no matching instances were found.');

            return [];
        }

        /** @var CopyShared[] $copyShareds */
        $copyShareds = [];
        foreach ($targetInstances as $targetInstance) {
            $copyShared = $this->constructCopyShared($output, $targetInstance, $sourceStage);

            if (null !== $copyShared) {
                $copyShareds[] = $copyShared;
            }
        }

        return $copyShareds;
    }

    /**
     * @throws Exception
     */
    private function constructCopyShared(OutputInterface $output, Instance $targetInstance, string $sourceStage): ?CopyShared
    {
        $sourceFilter = new Filter([$targetInstance->getServerName()], [$targetInstance->getEnvironmentName()], [$sourceStage]);
        $sourceInstances = $this->instanceService->getInstancesByFilter($sourceFilter);

        if (0 === count($sourceInstances)) {
            $this->io->warning('For instance '.$targetInstance->describe().' no matching source was found.');

            return null;
        }

        return new CopyShared($sourceInstances[0], $targetInstance);
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute().
     *
     * @param CopyShared $copyShared
     */
    protected function canProcessPayload($copyShared, OutputInterface $output): bool
    {
        if (!$copyShared instanceof CopyShared) {
            $this->io->writeln('Not a '.CopyShared::class);

            return false;
        }

        // does not make sense to copy from itself
        if ($copyShared->getSource()->equals($copyShared->getTarget())) {
            $this->io->warning('Cannot execute '.$copyShared->describe().': copy shared to itself does not make sense.');

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

            $this->io->text('copying folder '.$sharedFolder);
            $connection->copyFolderContent($sourceFolderPath, $targetFolderPath);
        }
    }
}
