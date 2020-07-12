<?php

namespace Agnes\Services\Task;

use Agnes\Models\Filter;
use Agnes\Models\Instance;
use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;
use Agnes\Models\Task\Run;
use Agnes\Services\Configuration\Task;
use Agnes\Services\InstanceService;
use Symfony\Component\Console\Style\StyleInterface;

class AfterTaskVisitor extends AbstractTaskVisitor
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @var Task
     */
    private $task;

    /**
     * AfterTaskVisitor constructor.
     */
    public function __construct(InstanceService $instanceService, TaskFactory $taskFactory, Task $task)
    {
        $this->instanceService = $instanceService;
        $this->taskFactory = $taskFactory;
        $this->task = $task;
    }

    public function visitRelease(Release $release)
    {
        return $this->createFrom(null, $release->getName());
    }

    public function visitDeploy(Deploy $deploy)
    {
        return $this->createFrom($deploy->getTarget(), $deploy->getReleaseOrCommitish());
    }

    public function visitRollback(Rollback $rollback)
    {
        return $this->createFrom($rollback->getTarget());
    }

    public function visitRun(Run $run)
    {
        return $this->createFrom($run->getTarget());
    }

    public function visitCopy(Copy $copy)
    {
        return $this->createFrom($copy->getTarget());
    }

    /**
     * @throws \Exception
     */
    private function createFrom(?Instance $instance, ?string $releaseOrCommitish = null): array
    {
        if (null !== $instance && null !== $this->task->getFilter() && !$this->task->getFilter()->instanceMatches($instance)) {
            return [];
        }

        $instances = $this->getMatchingInstances($instance);

        /** @var AbstractTask[] $task */
        $tasks = [];
        foreach ($instances as $instance) {
            $task = $this->createForInstance($instance, $releaseOrCommitish);
            if (null !== $task) {
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    /**
     * @throws \Exception
     */
    private function getMatchingInstances(?Instance $instance = null): array
    {
        if (!isset($this->task->getArguments()['target'])) {
            if (null === $instance) {
                $this->io->error($this->task->getName().' misses the required target argument (like arguments: { source: production }). skipping...');

                return [];
            }

            return [$instance];
        }

        $target = $this->task->getArguments()['target'];
        $filter = Filter::createFromInstanceWithOverrideInstanceSpecification($instance, $target);

        return $this->instanceService->getInstancesByFilter($filter);
    }

    /**
     * @throws \Exception
     */
    private function createForInstance(Instance $instance, ?string $releaseOrCommitish): ?AbstractTask
    {
        switch ($this->task->getName()) {
            case Deploy::NAME:
                if (null === $releaseOrCommitish) {
                    return null;
                }

                return $this->createDeployTask($instance, $releaseOrCommitish);
            case Copy::NAME:
                return $this->createCopyTask($instance);
            case Run::NAME:
                return $this->createRunTask($instance);
            default:
                return null;
        }
    }

    /**
     * @throws \Exception
     */
    private function createDeployTask(Instance $instance, string $releaseOrCommitish): ?Deploy
    {
        return $this->taskFactory->createDeploy($instance, $releaseOrCommitish);
    }

    /**
     * @throws \Exception
     */
    private function createCopyTask(Instance $instance): ?Copy
    {
        if (!isset($this->task->getArguments()['source'])) {
            $this->io->error($this->task->getName().' misses the required source argument (like arguments: { source: production }). skipping...');

            return null;
        }

        $source = $this->task->getArguments()['source'];

        return $this->taskFactory->createCopy($instance, $source);
    }

    private function createRunTask(Instance $instance): ?Run
    {
        if (!isset($this->task->getArguments()['script'])) {
            $this->io->error($this->task->getName().' misses the required script argument (like arguments: { script: clear_cache }). skipping...');

            return null;
        }

        $script = $this->task->getArguments()['script'];

        return $this->taskFactory->createRun($instance, $script);
    }
}
