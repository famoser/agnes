<?php

namespace Agnes\Actions;

use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\ConfigurationService;
use Agnes\Services\FileService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\ScriptService;
use Agnes\Services\SetupService;
use Http\Client\Exception;
use Symfony\Component\Console\Style\StyleInterface;

class PayloadFactory
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
     * @var InstallationService
     */
    private $installationService;

    /**
     * @var ScriptService
     */
    private $scriptService;

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var SetupService
     */
    private $setupService;

    private function createCopySharedActionFromDeployOrRollback(array $arguments, Instance $instance)
    {
        if (!isset($arguments['source'])) {
            $this->io->text('must specify source argument for a copy:shared action (like arguments: { source: production })');

            return;
        }

        $source = $arguments['source'];
        $action = $this->constructCopyShared($instance, $source);
        $copyShared = $action->createSingle($instance, $source);
        if (null === $copyShared) {
            return;
        }

        $this->executePayload($action, $copyShared);
    }

    /**
     * @return CopyShared[]
     *
     * @throws Exception
     */
    public function createCopySharedMany(string $target, string $sourceStage): array
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
     * @throws Exception
     */
    public function createDeploy(string $releaseOrCommitish, Instance $target): ?Deploy
    {
        if (!$this->fileService->allRequiredFilesExist($target)) {
            return null;
        }

        $setup = $this->setupService->getSetup($releaseOrCommitish);

        return new Deploy($setup, $target);
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    public function createManyDeploy(string $releaseOrCommitish, string $target)
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $instances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');

            return [];
        }

        /** @var Deploy[] $deploys */
        $deploys = [];
        $setup = null;
        foreach ($instances as $instance) {
            if (!$this->fileService->allRequiredFilesExist($instance)) {
                continue;
            }

            if (null === $setup) {
                $setup = $this->setupService->getSetup($releaseOrCommitish);
            }

            $deploys[] = new Deploy($setup, $instance);
        }

        return $deploys;
    }

    public function createRelease(string $commitish, string $name)
    {
        return new Release($commitish, $name);
    }

    /**
     * @return Rollback[]
     *
     * @throws Exception
     */
    public function createManyRollback(string $target, ?string $rollbackTo, ?string $rollbackFrom): array
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $instances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');

            return [];
        }

        /** @var Rollback[] $rollbacks */
        $rollbacks = [];
        foreach ($instances as $instance) {
            $rollback = $this->createRollback($instance, $rollbackTo, $rollbackFrom);
            if (null !== $rollback) {
                $rollbacks[] = $rollback;
            }
        }

        return $rollbacks;
    }

    public function createRollback(Instance $instance, ?string $rollbackTo, ?string $rollbackFrom)
    {
        $currentInstallation = $instance->getCurrentInstallation();

        // if no installation, can not rollback
        if (null === $currentInstallation) {
            $this->io->warning('No active installation, can not rollback.');

            return null;
        }

        // skip if rollback from does not match
        if (null !== $rollbackFrom && $currentInstallation->getSetup() !== $rollbackFrom) {
            $this->io->warning('Active installation does not match '.$rollbackFrom.'. skipping...');

            return null;
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
            $this->io->warning('For instance '.$instance->describe().' no matching rollback installation was found.');

            return null;
        }

        return new Rollback($instance, $upperBoundInstallation);
    }
}
