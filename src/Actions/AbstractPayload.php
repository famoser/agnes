<?php

namespace Agnes\Actions;

use Agnes\Services\PolicyService;
use Exception;

abstract class AbstractPayload
{
    /**
     * @throws Exception
     */
    abstract public function canExecute(PolicyService $policyService): bool;

    abstract public function describe(): string;
}
