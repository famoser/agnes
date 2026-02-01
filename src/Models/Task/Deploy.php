<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class Deploy extends AbstractTask
{
    public const TYPE = 'deploy';

    /**
     * Deployment constructor.
     */
    public function __construct(private Instance $target)
    {
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function describe(): string
    {
        return 'deploy to ' . $this->getTarget()->describe();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitDeploy($this);
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
