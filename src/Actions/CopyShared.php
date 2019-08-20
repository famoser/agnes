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
     * @param Instance $source
     * @param Instance $target
     */
    public function __construct(Instance $source, Instance $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    /**
     * @return Instance
     */
    public function getSource(): Instance
    {
        return $this->source;
    }

    /**
     * @return Instance
     */
    public function getTarget(): Instance
    {
        return $this->target;
    }

    /**
     * @param PolicyService $policyService
     * @return bool
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService): bool
    {
        return $policyService->canCopyShared($this);
    }

    /**
     * @return string
     */
    public function describe(): string
    {
        return "copes the shared data from " . $this->getSource()->describe() . " to " . $this->getTarget()->describe() . ".";
    }
}