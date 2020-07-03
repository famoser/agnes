<?php

namespace Agnes\Actions;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\PolicyService;
use Exception;

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
     */
    public function __construct(Instance $instance, Installation $target)
    {
        $this->instance = $instance;
        $this->target = $target;
    }

    public function getInstance(): Instance
    {
        return $this->instance;
    }

    public function getTarget(): Installation
    {
        return $this->target;
    }

    /**
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService): bool
    {
        return $policyService->canRollback($this);
    }

    public function describe(): string
    {
        return 'rollback '.$this->getInstance()->describe().' at '.$this->getInstance()->getCurrentInstallation()->getSetup()->getIdentification().'to '.$this->getTarget()->getSetup()->getIdentification();
    }
}
