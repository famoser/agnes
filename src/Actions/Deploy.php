<?php

namespace Agnes\Actions;

use Agnes\Models\Instance;
use Agnes\Models\Setup;
use Agnes\Services\PolicyService;
use Exception;

class Deploy extends AbstractPayload
{
    /**
     * @var Instance
     */
    private $target;

    /**
     * @var Setup
     */
    private $setup;

    /**
     * Deployment constructor.
     *
     * @param string[] $files
     */
    public function __construct(Setup $setup, Instance $target)
    {
        $this->setup = $setup;
        $this->target = $target;
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function getSetup(): Setup
    {
        return $this->setup;
    }

    /**
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService): bool
    {
        return $policyService->canDeploy($this);
    }

    public function describe(): string
    {
        return 'deploy '.$this->getSetup()->getIdentification().' to '.$this->getTarget()->describe();
    }
}
