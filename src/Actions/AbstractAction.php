<?php


namespace Agnes\Actions;


use Agnes\Services\PolicyService;
use Exception;

abstract class AbstractAction
{
    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * AbstractAction constructor.
     * @param PolicyService $policyService
     */
    public function __construct(PolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    /**
     * @param AbstractPayload[] $payloads
     * @throws Exception
     */
    public function executeMultiple(array $payloads)
    {
        foreach ($payloads as $payload) {
            $this->execute($payload);
        }
    }

    /**
     * @param AbstractPayload $payload
     * @throws Exception
     */
    public function execute(AbstractPayload $payload)
    {
        if (!$this->canExecute($payload)) {
            return;
        }

        $this->doExecute($payload);
    }

    /**
     * @param AbstractPayload[] $payloads
     * @return AbstractPayload[]
     * @throws Exception
     */
    public function filterCanExecute(array $payloads)
    {
        $result = [];

        foreach ($payloads as $payload) {
            if ($this->canExecute($payload)) {
                $result[] = $payload;
            }
        }

        return $result;
    }

    /**
     * @param AbstractPayload $payload
     * @return bool
     * @throws Exception
     */
    public function canExecute(AbstractPayload $payload): bool
    {
        if (!$this->canProcessPayload($payload)) {
            return false;
        }

        if (!$payload->canExecute($this->policyService)) {
            return false;
        }

        return true;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute()
     *
     * @param $payload
     * @return bool
     */
    protected abstract function canProcessPayload($payload): bool;

    /**
     * @param $copyShared
     */
    abstract protected function doExecute($copyShared);
}