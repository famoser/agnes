<?php

namespace Agnes\Actions;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

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
    public function canExecute(PolicyService $policyService, OutputInterface $output): bool
    {
        return $policyService->canRollback($this, $output);
    }

    public function describe(): string
    {
        return 'rollback '.$this->getInstance()->describe().' at '.$this->getInstance()->getCurrentInstallation()->getSetup()->getIdentification().'to '.$this->getTarget()->getSetup()->getIdentification();
    }
}
