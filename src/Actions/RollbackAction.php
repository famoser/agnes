<?php

namespace Agnes\Actions;

use Agnes\Models\Installation;
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
    public function createMany(string $target, ?string $rollbackTo, ?string $rollbackFrom, OutputInterface $output): array
    {
        if (null !== $rollbackFrom && $rollbackTo === $rollbackFrom) {
            $output->writeln('Can not rollback within same version. Rollback from '.$rollbackFrom.' to '.$rollbackTo.'.');

            return [];
        }

        $instances = $this->instanceService->getInstancesFromInstanceSpecification($target);
        if (0 === count($instances)) {
            $output->writeln('For target specification '.$target.' no matching instances were found.');

            return [];
        }

        /** @var Rollback[] $rollbacks */
        $rollbacks = [];
        foreach ($instances as $instance) {
            $currentInstallation = $instance->getCurrentInstallation();

            // if no installation, can not rollback
            if (null === $currentInstallation) {
                continue;
            }

            // skip if rollback from does not match
            if (null !== $rollbackFrom && $currentInstallation->getSetup() !== $rollbackFrom) {
                continue;
            }

            // if not target specified, simply take next lower
            $rollbackToMatcher = null;
            if (null !== $rollbackTo) {
                $rollbackToMatcher = function (Installation $installation) use ($rollbackTo) {
                    return $installation->getSetup()->getIdentification() === $rollbackTo;
                };
            }

            /** @var Installation|null $upperBoundInstallation */
            $upperBoundInstallation = null;
            foreach ($instance->getInstallations() as $installation) {
                if ($installation->getNumber() < $currentInstallation->getNumber() &&
                    (null === $upperBoundInstallation || $upperBoundInstallation->getNumber() < $installation->getNumber()) &&
                    (null === $rollbackToMatcher || $rollbackToMatcher($installation))) {
                    $upperBoundInstallation = $installation;
                }
            }

            if (null === $upperBoundInstallation) {
                $output->writeln('For instance '.$instance->describe().' no matching rollback installation was found.');
                continue;
            }

            $rollbacks[] = new Rollback($instance, $upperBoundInstallation);
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
        $previousReleasePath = $rollback->getTarget()->getFolder();
        $releaseFolder = $rollback->getInstance()->getCurrentInstallation()->getFolder();

        $output->writeln('executing rollback script');
        $deployScripts = $this->configurationService->getScripts('rollback');
        $rollback->getInstance()->getConnection()->executeScript($releaseFolder, $deployScripts, ['PREVIOUS_RELEASE_PATH' => $previousReleasePath]);

        $output->writeln('switching to previous release');
        $this->instanceService->switchInstallation($rollback->getInstance(), $rollback->getTarget());
        $output->writeln('previous release online');
    }
}
