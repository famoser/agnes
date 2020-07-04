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
    public function createSingle(Instance $target, string $sourceStage): ?CopyShared
    {
        return $this->constructCopyShared($target, $sourceStage);
    }

    /**
     * @return CopyShared[]
     *
     * @throws Exception
     */
    public function createMany(string $target, string $sourceStage): array
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
            $copyShared = $this->constructCopyShared($targetInstance, $sourceStage);

            if (null !== $copyShared) {
                $copyShareds[] = $copyShared;
            }
        }

        return $copyShareds;
    }

    /**
     * @throws Exception
     */
    private function constructCopyShared(Instance $targetInstance, string $sourceStage): ?CopyShared
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
    }

    /**
     * @param CopyShared $copyShared
     *
     * @throws Exception
     */
    protected function doExecute($copyShared, OutputInterface $output)
    {
    }
}
