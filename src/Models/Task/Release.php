<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

class Release extends AbstractTask
{
    /**
     * @var string
     */
    private $commitish;

    /**
     * @var string
     */
    private $name;

    /**
     * Release constructor.
     *
     * @param string $name
     */
    public function __construct(string $commitish, string $name = null)
    {
        $this->commitish = $commitish;
        $this->name = $name;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function name(): string
    {
        return null !== $this->name ? $this->name : $this->commitish;
    }

    public function describe(): string
    {
        return 'release '.$this->name();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor): bool
    {
        return $abstractActionVisitor->visitRelease($this);
    }

    public function getName(): string
    {
        return 'release';
    }
}
