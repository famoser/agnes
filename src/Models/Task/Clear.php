<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class Clear extends AbstractTask
{
    const NAME = 'clear';

    /**
     * @var Instance
     */
    private $target;

    /**
     * Deployment constructor.
     */
    public function __construct(Instance $target)
    {
        $this->target = $target;
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function describe(): string
    {
        return 'deploy to '.$this->getTarget()->describe();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitClear($this);
    }

    public function name(): string
    {
        return self::NAME;
    }
}
