<?php

namespace Agnes\Services\Task;

use Agnes\Models\Filter;
use Agnes\Models\Instance;
use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Decrypt;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Encrypt;
use Agnes\Models\Task\Rollback;
use Agnes\Models\Task\Run;
use Agnes\Services\Configuration\Task;
use Agnes\Services\InstanceService;
use Symfony\Component\Console\Style\StyleInterface;

class TaskConfigVisitor extends AbstractTaskVisitor
{
    public function __construct(private StyleInterface $io, private InstanceService $instanceService, private TaskFactory $taskFactory, private bool $buildExists, private Task $task)
    {
    }

    public function visitEncrypt(Encrypt $encrypt): array
    {
        return $this->createFrom($encrypt->getTarget());
    }

    public function visitDecrypt(Decrypt $decrypt): array
    {
        return $this->createFrom($decrypt->getTarget());
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

    public function visitDefault(AbstractTask $payload): array
    {
        return $this->createFrom();
    }

    private function createFrom(?Instance $instance = null): array
    {
        if (null !== $instance && null !== $this->task->getFilter() && !$this->task->getFilter()->instanceMatches($instance)) {
            return [];
        }

        $instances = $this->getMatchingInstances($instance);

        /** @var AbstractTask[] $tasks */
        $tasks = [];
        foreach ($instances as $instance) {
            $task = $this->createForInstance($instance);
            if (null !== $task) {
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    private function getMatchingInstances(?Instance $instance = null): array
    {
        if (!isset($this->task->getArguments()['target'])) {
            if (null === $instance) {
                $this->io->error($this->task->getName() . ' misses the required target argument (like arguments: { source: production }). skipping...');

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

    private function createDeployTask(Instance $instance): ?Deploy
    {
        return $this->taskFactory->createDeploy($instance);
    }

    private function createCopyTask(Instance $instance): ?Copy
    {
        if (!isset($this->task->getArguments()['source'])) {
            $this->io->error($this->task->getName() . ' misses the required source argument (like arguments: { source: production }). skipping...');

            return null;
        }

        $source = $this->task->getArguments()['source'];

        return $this->taskFactory->createCopy($instance, $source);
    }

    private function createRunTask(Instance $instance): ?Run
    {
        if (!isset($this->task->getArguments()['script'])) {
            $this->io->error($this->task->getName() . ' misses the required script argument (like arguments: { script: clear_cache }). skipping...');

            return null;
        }

        $script = $this->task->getArguments()['script'];

        return $this->taskFactory->createRun($instance, $script);
    }
}
