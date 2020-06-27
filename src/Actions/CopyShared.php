<?php

namespace Agnes\Actions;

use Agnes\Models\Instance;
use Agnes\Services\PolicyService;
use Exception;

class CopyShared extends AbstractPayload
{
    /**
     * @var Instance
     */
    private $source;

    /**
     * @var Instance
     */
    private $target;

    /**
     * CopyShared constructor.
     */
    public function __construct(Instance $source, Instance $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    public function getSource(): Instance
    {
        return $this->source;
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    /**
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService): bool
    {
        return $policyService->canCopyShared($this);
    }

    public function describe(): string
    {
        return 'copy shared data from '.$this->getSource()->describe().' to '.$this->getTarget()->describe();
    }
}
