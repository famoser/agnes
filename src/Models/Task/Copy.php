<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class Copy extends AbstractTask
{
    const NAME = 'copy';

    /**
     * @var Instance
     */
    private $source;

    /**
     * @var Instance
     */
    private $target;

    /**
     * Copy constructor.
     */
    public function __construct(Instance $source, Instance $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    public function getSource(): Instance
    {
        return $this->source;
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function describe(): string
    {
        return 'copy shared data from '.$this->getSource()->describe().' to '.$this->getTarget()->describe();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitCopy($this);
    }

    public function name(): string
    {
        return self::NAME;
    }
}
