<?php

namespace Agnes\Services;

use Agnes\Actions\AbstractPayload;
use Agnes\Actions\CopyShared;
use Agnes\Actions\Deploy;
use Agnes\Actions\Release;
use Agnes\Actions\Rollback;
use Agnes\Actions\Visitors\ExecutionVisitor;
use Agnes\Actions\Visitors\ValidatorVisitor;
use Agnes\Models\Build;
use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Http\Client\Exception;
use Symfony\Component\Console\Style\StyleInterface;
use function Agnes\Actions\;

class TaskService
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var ExecutionVisitor
     */
    private $executionVisitor;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var SetupService
     */
    private $setupService;

    /**
     * @var AbstractPayload[]
     */
    private $payloads = [];

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
    private function addDeploy(string $releaseOrCommitish, Instance $target)
    {
        if (!$this->fileService->allRequiredFilesExist($target)) {
            return null;
        }

        // TODO
        if (null === $setup) {
            $content = $this->githubService->getBuildByReleaseName($releaseOrCommitish);
            if ($content === null) {
                $deploys[] = new Build($releaseOrCommitish);
            }
        }

        $deploy = new Deploy($setup, $target);
        $this->addTask($deploy);
    }

    private function addTask(AbstractPayload $payload)
    {
        $this->tasks[] = $payload;

        $actions = $this->configurationService->getActions($payload);
        foreach ($actions as $action) {
            $task = $this->createPayload($payload, $action);
            $this->addTask($task);
        }
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    public function addDeployFromTargetSpecification(string $releaseOrCommitish, string $target)
    {
        $instances = $this->instanceService->getInstancesBySpecification($target);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');
            return;
        }

        $setup = null;
        foreach ($instances as $instance) {
            $this->addDeploy($releaseOrCommitish, $instance);
        }
    }

    public function createRelease(string $commitish, string $name)
    {
        return new Release($commitish, $name);
    }

    /**
     * @throws Exception
     */
    public function addManyRollback(string $target, ?string $rollbackTo, ?string $rollbackFrom)
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $instances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($instances)) {
            $this->io->warning('For target specification '.$target.' no matching instances were found.');
            return;
        }

        foreach ($instances as $instance) {
            $rollback = $this->createRollback($instance, $rollbackTo, $rollbackFrom);
            if (null !== $rollback) {
                $this->addTask($rollback);
            }
        }
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
        if (null !== $rollbackFrom && $currentInstallation->getReleaseOrCommitish() !== $rollbackFrom) {
            $this->io->warning('Active installation does not match '.$rollbackFrom.'. skipping...');

            return null;
        }

        // if not target specified, simply take next lower
        $rollbackToMatcher = null;
        if (null !== $rollbackTo) {
            $rollbackToMatcher = function (Installation $installation) use ($rollbackTo) {
                return $installation->getReleaseOrCommitish() === $rollbackTo;
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


    /**
     * @param AbstractPayload[] $payloads
     *
     * @throws \Exception
     */
    public function execute()
    {
        foreach ($payloads as $item) {
            if (!$item->accept($this->validatorVisitor)) {
                $this->io->text('skipping '.$item->describe().' ...');

                continue;
            }

            $this->io->text('executing '.$item->describe().' ...');
            $item->accept($this->executionVisitor);
        }
    }
}
