<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services;

use Agnes\Models\Filter;
use Agnes\Models\Task\AbstractTask;
use Agnes\Services\Configuration\Task;
use Agnes\Services\Policy\AbstractPolicyVisitor;
use Agnes\Services\Task\ExecutionVisitor;
use Agnes\Services\Task\PolicyVisitor;
use Agnes\Services\Task\TaskConfigVisitor;
use Agnes\Services\Task\TaskFactory;
use Symfony\Component\Console\Style\StyleInterface;

class TaskService
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
     * @var FileService
     */
    private $fileService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @var ExecutionVisitor
     */
    private $executionVisitor;

    /**
     * ExecutionVisitor constructor.
     */
    public function __construct(StyleInterface $io, ConfigurationService $configurationService, FileService $fileService, GithubService $githubService, InstallationService $installationService, InstanceService $instanceService, ScriptService $scriptService)
    {
        $this->io = $io;
        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
        $this->fileService = $fileService;
        $this->githubService = $githubService;

        $this->taskFactory = new TaskFactory($io, $fileService, $githubService, $instanceService);
        $this->executionVisitor = new ExecutionVisitor($io, $configurationService, $fileService, $githubService, $installationService, $instanceService, $scriptService);
    }

    /**
     * @return AbstractTask[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function addReleaseTask(string $commitish, string $name): void
    {
        $this->ensureBuild($commitish, false);

        $release = $this->taskFactory->createRelease($name);
        $this->addTask($release);
    }

    public function addBuildTask(string $commitish): void
    {
        $task = $this->taskFactory->createBuild($commitish);
        $this->addTask($task);
    }

    public function addRunTask(string $target, string $script): void
    {
        $instances = $this->instanceService->getInstancesBySpecification($target);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');

            return;
        }

        $scriptModel = $this->configurationService->getScriptByName($script);
        if (null === $scriptModel) {
            $this->io->error('No script by the name '.$script.' was found.');

            return;
        }

        foreach ($instances as $instance) {
            $task = $this->taskFactory->createRun($instance, $script);
            $this->addTask($task);
        }
    }

    public function addDeployTasks(string $target, string $releaseOrCommitish): void
    {
        $instances = $this->instanceService->getInstancesBySpecification($target);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');

            return;
        }

        $this->ensureBuild($releaseOrCommitish);

        $setup = null;
        foreach ($instances as $instance) {
            $task = $this->taskFactory->createDeploy($instance);
            $this->addTask($task);
        }
    }

    public function addClearTask(string $target): void
    {
        $instances = $this->instanceService->getInstancesBySpecification($target);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');

            return;
        }

        $setup = null;
        foreach ($instances as $instance) {
            $task = $this->taskFactory->createClear($instance);
            $this->addTask($task);
        }
    }

    public function addRollbackTasks(string $target, ?string $rollbackTo, ?string $rollbackFrom): void
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $instances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($instances)) {
            $this->io->warning('For target specification '.$target.' no matching instances were found.');

            return;
        }

        foreach ($instances as $instance) {
            $rollback = $this->taskFactory->createRollback($instance, $rollbackTo, $rollbackFrom);
            $this->addTask($rollback);
        }
    }

    public function addCopyTasks(string $target, string $sourceStage): void
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $targetInstances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($targetInstances)) {
            $this->io->warning('For target specification '.$target.' no matching instances were found.');

            return;
        }

        foreach ($targetInstances as $targetInstance) {
            $copy = $this->taskFactory->createCopy($targetInstance, $sourceStage);
            $this->addTask($copy);
        }
    }

    /**
     * @var bool
     */
    private $built = false;

    private function ensureBuild(string $releaseOrCommitish, bool $allowDownload = true): void
    {
        if ($this->built) {
            return;
        }

        $this->built = true;

        if ($allowDownload && null !== $download = $this->taskFactory->createDownload($releaseOrCommitish)) {
            $this->addTask($download);
        } else {
            $task = $this->taskFactory->createBuild($releaseOrCommitish);
            $this->addTask($task);
        }
    }

    private function addTask(?AbstractTask $task): void
    {
        if (null === $task) {
            return;
        }

        $this->tasks[] = $task;
    }

    /**
     * @var AbstractTask[]
     */
    private $tasks = [];

    public function executeAll(): void
    {
        foreach ($this->tasks as $task) {
            $this->executeTask($task);
        }
    }

    private function executeTask(AbstractTask $task, bool $subSection = false): void
    {
        if ($subSection) {
            $this->io->newLine();
            $this->io->text($task->describe());
        } else {
            $this->io->section($task->describe());
        }

        // check if policies conflict
        $policyVisitor = new PolicyVisitor($this->io, $this->instanceService, $this->executionVisitor->getBuildResult());
        /** @var AbstractPolicyVisitor $taskPolicyVisitor */
        $taskPolicyVisitor = $task->accept($policyVisitor);
        if (!$taskPolicyVisitor->validate()) {
            $this->io->warning('skipping.');

            return;
        }

        // check for conflicting policies
        $policies = $this->configurationService->getPoliciesForTask($task->type());
        foreach ($policies as $policy) {
            if (!$policy->accept($taskPolicyVisitor)) {
                $this->io->warning('skipping.');

                return;
            }
        }

        // execute pre-task jobs
        $taskConfigs = $this->configurationService->getBeforeTasks($task->type());
        $this->executeTaskConfigs($taskConfigs, $task);

        // execute
        if (!$task->accept($this->executionVisitor)) {
            $this->io->error('failed.');

            return;
        }

        $this->io->text('finished.');
        $this->io->newLine();

        // execute post-task jobs
        $taskConfigs = $this->configurationService->getAfterTasks($task->type());
        $this->executeTaskConfigs($taskConfigs, $task);
    }

    /**
     * @param Task[] $taskConfigs
     */
    private function executeTaskConfigs(array $taskConfigs, AbstractTask $task): void
    {
        foreach ($taskConfigs as $afterTaskConfig) {
            $taskVisitor = new TaskConfigVisitor($this->instanceService, $this->taskFactory, $this->executionVisitor->buildExists(), $afterTaskConfig);
            $afterTasks = $task->accept($taskVisitor);
            foreach ($afterTasks as $afterTask) {
                $this->executeTask($afterTask, true);
            }
        }
    }
}
