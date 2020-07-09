<?php

namespace Agnes\Models\Task;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class Rollback extends AbstractTask
{
    /**
     * @var Instance
     */
    private $instance;

    /**
     * @var Installation
     */
    private $target;

    /**
     * Rollback constructor.
     */
    public function __construct(Instance $instance, Installation $target)
    {
        $this->instance = $instance;
        $this->target = $target;
    }

    public function getInstance(): Instance
    {
        return $this->instance;
    }

    public function getTarget(): Installation
    {
        return $this->target;
    }

    public function describe(): string
    {
        return 'rollback '.$this->getInstance()->describe().' at '.$this->getInstance()->getCurrentInstallation()->getReleaseOrCommitish().'to '.$this->getTarget()->getReleaseOrCommitish();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor): bool
    {
        return $abstractActionVisitor->visitRollback($this);
    }

    public function name(): string
    {
        return 'rollback';
    }
}
