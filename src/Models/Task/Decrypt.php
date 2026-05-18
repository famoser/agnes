<?php

namespace Agnes\Models\Task;

use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

readonly class Decrypt extends AbstractTask
{
    public const TYPE = 'decrypt';

    public function __construct(private Instance $target, private bool $diff)
    {
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function isDiff(): bool
    {
        return $this->diff;
    }

    public function describe(): string
    {
        return $this->diff ? 'check whether all decrypted files match to the encrypted files' : 'decrypt all files that are marked for encryption';
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitDecrypt($this);
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
