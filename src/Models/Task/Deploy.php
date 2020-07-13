<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class Deploy extends AbstractTask
{
    const NAME = 'deploy';

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
        return 'deploying to '.$this->getTarget()->describe();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitDeploy($this);
    }

    public function name(): string
    {
        return self::NAME;
    }
}
