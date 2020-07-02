<?php

namespace Agnes\Actions;

use Agnes\Models\Instance;
use Agnes\Models\Setup;
use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var string[]
     */
    private $filePaths;

    /**
     * Deployment constructor.
     *
     * @param string[] $files
     */
    public function __construct(Setup $setup, Instance $target, array $files)
    {
        $this->setup = $setup;
        $this->target = $target;
        $this->filePaths = $files;
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
     * @return string[]
     */
    public function getFilePaths(): array
    {
        return $this->filePaths;
    }

    /**
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService, OutputInterface $output): bool
    {
        return $policyService->canDeploy($this, $output);
    }

    public function describe(): string
    {
        return 'deploy '.$this->getSetup()->getIdentification().' to '.$this->getTarget()->describe();
    }
}
