<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

class Release extends AbstractTask
{
    const NAME = 'release';

    /**
     * @var string
     */
    private $name;

    /**
     * Release constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function describe(): string
    {
        return 'release '.$this->name();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitRelease($this);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
