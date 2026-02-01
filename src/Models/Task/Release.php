<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

readonly class Release extends AbstractTask
{
    public const TYPE = 'release';

    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function describe(): string
    {
        return 'release ' . $this->getName();
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
