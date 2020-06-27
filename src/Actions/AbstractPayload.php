<?php

namespace Agnes\Actions;

use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractPayload
{
    /**
     * @throws Exception
     */
    abstract public function canExecute(PolicyService $policyService, OutputInterface $output): bool;

    abstract public function describe(): string;
}
