<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;
use Exception;

abstract class AbstractTask
{
    /**
     * @throws Exception
     */
    abstract public function accept(AbstractTaskVisitor $abstractActionVisitor): bool;

    abstract public function describe(): string;

    abstract public function name(): string;
}
