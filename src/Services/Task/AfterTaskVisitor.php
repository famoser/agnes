<?php

namespace Agnes\Services\Task;

use Agnes\Models\Instance;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
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

    public function visitDeploy(Deploy $deploy)
    {
        return $this->createFromInstance($deploy->getTarget());
    }

    public function visitRollback(Rollback $rollback)
    {
        return $this->createFromInstance($rollback->getTarget());
    }

    public function visitRun(Run $run)
    {
        return $this->createFromInstance($run->getTarget());
    }

    public function visitCopy(Copy $copy)
    {
        return $this->createFromInstance($copy->getTarget());
    }

    private function createFromInstance(Instance $instance)
    {
        if (!$this->task->getFilter()->instanceMatches($instance)) {
            return null;
        }

        switch ($this->task->getName()) {
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
    private function createCopyTask(Instance $instance): ?Copy
    {
        if (!isset($arguments['source'])) {
            $this->io->error($this->task->getName().' misses the required source argument (like arguments: { source: production }). skipping...');

            return null;
        }

        $source = $arguments['source'];

        return $this->taskFactory->createCopy($instance, $source);
    }

    private function createRunTask(Instance $instance): ?Run
    {
        if (!isset($arguments['script'])) {
            $this->io->error($this->task->getName().' misses the required script argument (like arguments: { script: clear_cache }). skipping...');

            return null;
        }

        $script = $arguments['script'];

        return $this->taskFactory->createRun($instance, $script);
    }
}
