<?php

namespace Agnes\Services;

use Agnes\Models\Filter;
use Agnes\Models\Task\AbstractTask;
use Agnes\Services\Policy\AbstractPolicyVisitor;
use Agnes\Services\Task\AfterTaskVisitor;
use Agnes\Services\Task\ExecutionVisitor;
use Agnes\Services\Task\PolicyVisitor;
use Agnes\Services\Task\TaskFactory;
use Http\Client\Exception;
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

    public function addReleaseTask(string $commitish, string $name)
    {
        $this->ensureBuild($commitish, false);

        $release = $this->taskFactory->createRelease($name);
        $this->addTask($release);
    }

    public function addBuildTask(string $commitish)
    {
        $task = $this->taskFactory->createBuild($commitish);
        $this->addTask($task);
    }

    public function addRunTask(string $target, string $script)
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

    /**
     * @throws \Exception
     * @throws Exception
     */
    public function addDeployTasks(string $target, string $releaseOrCommitish)
    {
        $instances = $this->instanceService->getInstancesBySpecification($target);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');

            return;
        }

        $isRelease = $this->ensureBuild($releaseOrCommitish);

        $setup = null;
        foreach ($instances as $instance) {
            $task = $this->taskFactory->createDeploy($instance);
            $this->addTask($task);
        }
    }

    /**
     * @throws \Exception
     */
    public function addRollbackTasks(string $target, ?string $rollbackTo, ?string $rollbackFrom)
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

    /**
     * @throws Exception
     */
    public function addCopyTasks(string $target, string $sourceStage)
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
     * @var bool|null
     */
    private $isRelease = null;

    private function ensureBuild(string $releaseOrCommitish, bool $allowDownload = true)
    {
        if (null !== $this->isRelease) {
            return $this->isRelease;
        }

        if ($allowDownload && null !== $download = $this->taskFactory->createDownload($releaseOrCommitish)) {
            $this->isRelease = true;
            $this->addTask($download);
        } else {
            $this->isRelease = false;
            $task = $this->taskFactory->createBuild($releaseOrCommitish);
            $this->addTask($task);
        }

        return $this->isRelease;
    }

    private function addTask(?AbstractTask $task)
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

    /**
     * @throws \Exception
     */
    public function executeAll()
    {
        foreach ($this->tasks as $task) {
            $this->executeTask($task);
        }
    }

    /**
     * @throws \Exception
     */
    private function executeTask(AbstractTask $task)
    {
        $this->io->section($task->describe());

        // check if policies conflict
        $policyVisitor = new PolicyVisitor($this->io, $this->instanceService, $this->executionVisitor->getBuildResult());
        /** @var AbstractPolicyVisitor $taskPolicyVisitor */
        $taskPolicyVisitor = $task->accept($policyVisitor);
        if (!$taskPolicyVisitor->validate()) {
            $this->io->warning('skipping.');

            return;
        }

        // check for conflicting policies
        $policies = $this->configurationService->getPoliciesForTask($task->name());
        foreach ($policies as $policy) {
            if (!$policy->accept($taskPolicyVisitor)) {
                $this->io->warning('skipping.');

                return;
            }
        }

        if (!$task->accept($this->executionVisitor)) {
            $this->io->error('failed.');

            return;
        }

        $this->io->text('finished.');

        // execute post-task jobs
        $afterTaskConfigs = $this->configurationService->getAfterTasks($task->name());
        foreach ($afterTaskConfigs as $afterTaskConfig) {
            $afterTaskVisitor = new AfterTaskVisitor($this->instanceService, $this->taskFactory, $this->executionVisitor->buildExists(), $afterTaskConfig);
            $afterTasks = $task->accept($afterTaskVisitor);
            foreach ($afterTasks as $afterTask) {
                $this->executeTask($afterTask);
            }
        }
    }
}
