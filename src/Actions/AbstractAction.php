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
     * @param PolicyService $policyService
     */
    public function __construct(PolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    /**
     * @param AbstractPayload $payload
     * @param OutputInterface $output
     */
    public function execute(AbstractPayload $payload, OutputInterface $output)
    {
        $this->doExecute($payload, $output);
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
     * @param $payload
     * @param OutputInterface $output
     */
    abstract protected function doExecute($payload, OutputInterface $output);
}