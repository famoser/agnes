<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class Run extends AbstractTask
{
    const NAME = 'run';

    /**
     * @var Instance
     */
    private $target;

    /**
     * @var string
     */
    private $name;

    public function __construct(string $name, Instance $target)
    {
        $this->name = $name;
        $this->target = $target;
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function describe(): string
    {
        return 'running '.$this->getName().' on '.$this->getTarget()->describe();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitRun($this);
    }

    public function name(): string
    {
        return self::NAME;
    }
}
