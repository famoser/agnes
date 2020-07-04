<?php

namespace Agnes\Actions;

use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractAction
{
    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * AbstractAction constructor.
     */
    public function __construct(PolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    public function execute(AbstractPayload $payload, OutputInterface $output)
    {
        $this->doExecute($payload, $output);
    }

    /**
     * @throws Exception
     */
    public function canExecute(AbstractPayload $payload, OutputInterface $output): bool
    {
        if (!$this->canProcessPayload($payload, $output)) {
            return false;
        }

        if (!$payload->canExecute($this->policyService)) {
            return false;
        }

        return true;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute().
     *
     * @param $payload
     */
    abstract protected function canProcessPayload($payload, OutputInterface $output): bool;

    /**
     * @param $payload
     */
    abstract protected function doExecute($payload, OutputInterface $output);
}
