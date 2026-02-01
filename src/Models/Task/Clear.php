<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

readonly class Clear extends AbstractTask
{
    public const TYPE = 'clear';

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
        return $abstractActionVisitor->visitClear($this);
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
