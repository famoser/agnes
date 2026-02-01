<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

readonly class Run extends AbstractTask
{
    public const TYPE = 'run';

    public function __construct(private string $script, private Instance $target)
    {
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function getScript(): string
    {
        return $this->script;
    }

    public function describe(): string
    {
        return 'run ' . $this->getScript() . ' on ' . $this->getTarget()->describe();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitRun($this);
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
