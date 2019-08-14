<?php


namespace Agnes\Actions;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\PolicyService;

class Rollback extends AbstractPayload
{
    /**
     * @var Instance
     */
    private $instance;

    /**
     * @var Installation
     */
    private $target;

    /**
     * Rollback constructor.
     * @param Instance $instance
     * @param Installation $target
     */
    public function __construct(Instance $instance, Installation $target)
    {
        $this->instance = $instance;
        $this->target = $target;
    }

    /**
     * @return Instance
     */
    public function getInstance(): Instance
    {
        return $this->instance;
    }

    /**
     * @return Installation
     */
    public function getTarget(): Installation
    {
        return $this->target;
    }

    /**
     * @param PolicyService $policyService
     * @return bool
     * @throws \Exception
     */
    public function canExecute(PolicyService $policyService): bool
    {
        return $policyService->canRollback($this);
    }
}