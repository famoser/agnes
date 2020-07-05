<?php

namespace Agnes\Actions;

use Agnes\Actions\Visitors\AbstractActionVisitor;
use Exception;

abstract class AbstractPayload
{
    /**
     * @throws Exception
     */
    abstract public function accept(AbstractActionVisitor $abstractActionVisitor): bool;

    abstract public function describe(): string;
}
