<?php

namespace Agnes\Actions;

use Agnes\Models\Filter;
use Agnes\Models\Instance;
use Agnes\Services\FileService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Agnes\Services\ScriptService;
use Agnes\Services\SetupService;
use Http\Client\Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

class DeployAction extends AbstractAction
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var InstallationService
     */
    private $installationService;

    /**
     * @var ScriptService
     */
    private $scriptService;

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var SetupService
     */
    private $setupService;

    /**
     * DeployAction constructor.
     */
    public function __construct(PolicyService $policyService, StyleInterface $io, InstanceService $instanceService, InstallationService $installationService, ScriptService $scriptService, FileService $fileService, SetupService $setupService)
    {
        parent::__construct($policyService);

        $this->io = $io;
        $this->instanceService = $instanceService;
        $this->installationService = $installationService;
        $this->scriptService = $scriptService;
        $this->fileService = $fileService;
        $this->setupService = $setupService;
    }

    /**
     * @throws Exception
     */
    public function createSingle(string $releaseOrCommitish, Instance $target): ?Deploy
    {
        if (!$this->fileService->allRequiredFilesExist($target)) {
            return null;
        }

        $setup = $this->setupService->getSetup($releaseOrCommitish);

        return new Deploy($setup, $target);
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    public function createMany(string $releaseOrCommitish, string $target)
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $instances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($instances)) {
            $this->io->error('For target specification '.$target.' no matching instances were found.');

            return [];
        }

        /** @var Deploy[] $deploys */
        $deploys = [];
        $setup = null;
        foreach ($instances as $instance) {
            if (!$this->fileService->allRequiredFilesExist($instance)) {
                continue;
            }

            if (null === $setup) {
                $setup = $this->setupService->getSetup($releaseOrCommitish);
            }

            $deploys[] = new Deploy($setup, $instance);
        }

        return $deploys;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute().
     *
     * @param Deploy $deploy
     */
    protected function canProcessPayload($deploy, OutputInterface $output): bool
    {
    }

    /**
     * @param Deploy $deploy
     *
     * @throws \Exception
     */
    protected function doExecute($deploy, OutputInterface $output)
    {
    }
}
