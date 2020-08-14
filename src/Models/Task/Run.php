<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class Run extends AbstractTask
{
    const TYPE = 'run';

    /**
     * @var Instance
     */
    private $target;

    /**
     * @var string
     */
    private $script;

    public function __construct(string $script, Instance $target)
    {
        $this->script = $script;
        $this->target = $target;
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
        return 'run '.$this->getScript().' on '.$this->getTarget()->describe();
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
