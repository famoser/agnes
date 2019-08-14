<?php


namespace Agnes\Actions;


use Agnes\Services\PolicyService;

abstract class AbstractPayload
{
    /**
     * @param PolicyService $policyService
     * @return bool
     * @throws \Exception
     */
    abstract public function canExecute(PolicyService $policyService): bool;
}