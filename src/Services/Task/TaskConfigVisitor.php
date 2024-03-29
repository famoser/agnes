<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Task;

use Agnes\Models\Filter;
use Agnes\Models\Instance;
use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\Build;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;
use Agnes\Models\Task\Run;
use Agnes\Services\Configuration\Task;
use Agnes\Services\InstanceService;
use Symfony\Component\Console\Style\StyleInterface;

class TaskConfigVisitor extends AbstractTaskVisitor
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
     * @var bool
     */
    private $buildExists;

    /**
     * AfterTaskVisitor constructor.
     */
    public function __construct(InstanceService $instanceService, TaskFactory $taskFactory, bool $buildExists, Task $task)
    {
        $this->instanceService = $instanceService;
        $this->taskFactory = $taskFactory;
        $this->buildExists = $buildExists;
        $this->task = $task;
    }

    public function visitRelease(Release $release): array
    {
        return $this->createFrom();
    }

    public function visitBuild(Build $build): array
    {
        return $this->createFrom();
    }

    public function visitDeploy(Deploy $deploy): array
    {
        return $this->createFrom($deploy->getTarget());
    }

    public function visitRollback(Rollback $rollback): array
    {
        return $this->createFrom($rollback->getTarget());
    }

    public function visitRun(Run $run): array
    {
        return $this->createFrom($run->getTarget());
    }

    public function visitCopy(Copy $copy): array
    {
        return $this->createFrom($copy->getTarget());
    }

    /**
     * @throws \Exception
     */
    private function createFrom(?Instance $instance = null): array
    {
        if (null !== $instance && null !== $this->task->getFilter() && !$this->task->getFilter()->instanceMatches($instance)) {
            return [];
        }

        $instances = $this->getMatchingInstances($instance);

        /** @var AbstractTask[] $task */
        $tasks = [];
        foreach ($instances as $instance) {
            $task = $this->createForInstance($instance);
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
        if (null === $instance) {
            $filter = Filter::createFromInstanceSpecification($target);
        } else {
            $filter = Filter::createFromInstanceWithOverrideInstanceSpecification($instance, $target);
        }

        return $this->instanceService->getInstancesByFilter($filter);
    }

    /**
     * @throws \Exception
     */
    private function createForInstance(Instance $instance): ?AbstractTask
    {
        switch ($this->task->getTask()) {
            case Deploy::TYPE:
                if (!$this->buildExists) {
                    return null;
                }

                return $this->createDeployTask($instance);
            case Copy::TYPE:
                return $this->createCopyTask($instance);
            case Run::TYPE:
                return $this->createRunTask($instance);
            default:
                return null;
        }
    }

    /**
     * @throws \Exception
     */
    private function createDeployTask(Instance $instance): ?Deploy
    {
        return $this->taskFactory->createDeploy($instance);
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
