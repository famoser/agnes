<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

class Build extends AbstractTask
{
    public const TYPE = 'build';

    /**
     * Build constructor.
     */
    public function __construct(private string $commitish)
    {
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitBuild($this);
    }

    public function describe(): string
    {
        return 'build ' . $this->commitish;
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
