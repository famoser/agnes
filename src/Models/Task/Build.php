<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

class Build extends AbstractTask
{
    const NAME = 'build';

    /**
     * @var string
     */
    private $commitish;

    /**
     * Build constructor.
     */
    public function __construct(string $commtish)
    {
        $this->commitish = $commtish;
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
        return 'building '.$this->commitish;
    }

    public function name(): string
    {
        return self::NAME;
    }
}
