<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

class Release extends AbstractTask
{
    const TYPE = 'release';

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

    public function getName(): string
    {
        return $this->name;
    }

    public function describe(): string
    {
        return 'release '.$this->getName();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitRelease($this);
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
