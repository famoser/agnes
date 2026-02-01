<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

abstract class AbstractTask
{
    /**
     * @throws \Exception
     */
    abstract public function accept(AbstractTaskVisitor $abstractActionVisitor);

    abstract public function describe(): string;

    abstract public function type(): string;
}
