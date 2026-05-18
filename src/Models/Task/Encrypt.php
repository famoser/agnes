<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

readonly class Encrypt extends AbstractTask
{
    public const TYPE = 'encrypt';

    public function __construct(private Instance $target, private bool $overwrite)
    {
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    public function describe(): string
    {
        return $this->overwrite ? 'encrypt all changed files that are marked for encryption' : '(re-)encrypt all files that are marked for encryption';
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitEncrypt($this);
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
