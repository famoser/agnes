<?php

namespace Agnes\Actions;

use Agnes\Services\ConfigurationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackAction extends AbstractAction
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
     * RollbackService constructor.
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, InstanceService $instanceService)
    {
        parent::__construct($policyService);

        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
    }

    /**
     * @return Rollback[]
     *
     * @throws Exception
     */
    public function createMany(string $target, ?string $rollbackTo, ?string $rollbackFrom)
    {
        $instances = $this->instanceService->getInstancesFromInstanceSpecification($target);

        /** @var Rollback[] $rollbacks */
        $rollbacks = [];
        foreach ($instances as $instance) {
            $rollbackTarget = $instance->getRollbackTarget($rollbackTo, $rollbackFrom);
            if (null !== $rollbackTarget) {
                $rollbacks[] = new Rollback($instance, $rollbackTarget);
            }
        }

        return $rollbacks;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute().
     *
     * @param Rollback $payload
     */
    protected function canProcessPayload($payload, OutputInterface $output): bool
    {
        if (!$payload instanceof Rollback) {
            $output->writeln('Not a '.Rollback::class);

            return false;
        }

        return true;
    }

    /**
     * @param Rollback $rollback
     *
     * @throws Exception
     */
    protected function doExecute($rollback, OutputInterface $output)
    {
        $previousReleasePath = $rollback->getTarget()->getPath();
        $releaseFolder = $rollback->getInstance()->getCurrentInstallation()->getPath();

        $output->writeln('executing rollback script');
        $deployScripts = $this->configurationService->getScripts('rollback');
        $rollback->getInstance()->getConnection()->executeScript($releaseFolder, $deployScripts, ['PREVIOUS_RELEASE_PATH' => $previousReleasePath]);

        $output->writeln('switching to previous release');
        $this->instanceService->switchRelease($rollback->getInstance(), $rollback->getTarget()->getRelease());
        $output->writeln('previous release online');
    }
}
