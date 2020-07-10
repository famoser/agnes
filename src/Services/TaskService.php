<?php

namespace Agnes\Services;

use Agnes\Models\Filter;
use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\CopyShared;
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
     * @var PolicyVisitor
     */
    private $policyVisitor;

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
        $this->policyVisitor = new PolicyVisitor($io, $instanceService);
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

        $release = $this->taskFactory->createRelease($commitish, $name);
        $this->addTask($release);
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    public function addDeployTasks(string $releaseOrCommitish, string $target)
    {
        $instances = $this->instanceService->getInstancesBySpecification($target);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');

            return;
        }

        $this->ensureBuild($releaseOrCommitish);

        $setup = null;
        foreach ($instances as $instance) {
            $task = $this->taskFactory->createDeploy($releaseOrCommitish, $instance);
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
    public function addCopySharedTasks(string $target, string $sourceStage)
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $targetInstances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($targetInstances)) {
            $this->io->warning('For target specification '.$target.' no matching instances were found.');

            return;
        }

        /** @var CopyShared[] $copyShareds */
        $copyShareds = [];
        foreach ($targetInstances as $targetInstance) {
            $copyShared = $this->taskFactory->createCopyShared($targetInstance, $sourceStage);
            $this->addTask($copyShared);
        }
    }

    /**
     * @var string[]
     */
    private $builtCommitish = [];

    private function ensureBuild(string $releaseOrCommitish, bool $allowDownload = true)
    {
        if (in_array($releaseOrCommitish, $this->builtCommitish)) {
            return;
        }

        if ($allowDownload) {
            $download = $this->taskFactory->createDownload($releaseOrCommitish);
            $this->addTask($download);
        }

        $task = $this->taskFactory->createBuild($releaseOrCommitish);
        $this->addTask($task);
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
        $this->io->text('executing '.$task->describe().' ...');

        // check if policies conflict
        /** @var AbstractPolicyVisitor $policyVisitor */
        $policyVisitor = $task->accept($this->policyVisitor);
        $policies = $this->configurationService->getPolicies($task->name());
        foreach ($policies as $policy) {
            if (!$policy->accept($policyVisitor)) {
                $this->io->warning('skipping '.$task->describe().' ...');

                return;
            }
        }

        if (!$task->accept($this->executionVisitor)) {
            $this->io->error('task '.$task->describe().' failed; will not execute post-task tasks...');

            return;
        }

        // execute post-task jobs
        $afterTaskConfigs = $this->configurationService->getAfterTasks($task->name());
        foreach ($afterTaskConfigs as $afterTaskConfig) {
            $afterTaskVisitor = new AfterTaskVisitor($this->instanceService, $this->taskFactory, $afterTaskConfig);
            $afterTasks = $task->accept($afterTaskVisitor);
            foreach ($afterTasks as $afterTask) {
                $this->executeTask($afterTask);
            }
        }

        $this->io->text('finished '.$task->describe().' ...');
    }
}
