<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

readonly class Copy extends AbstractTask
{
    public const TYPE = 'copy';

    public function __construct(private Instance $source, private Instance $target)
    {
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
        return 'copy shared data from ' . $this->getSource()->describe() . ' to ' . $this->getTarget()->describe();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitCopy($this);
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
