<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class CopyShared extends AbstractTask
{
    const NAME = 'oopy_shared';

    /**
     * @var Instance
     */
    private $source;

    /**
     * @var Instance
     */
    private $target;

    /**
     * CopyShared constructor.
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
        return $abstractActionVisitor->visitCopyShared($this);
    }

    public function name(): string
    {
        return self::NAME;
    }
}
