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
    public function createSingle(string $releaseOrCommitish, Instance $target, OutputInterface $output): ?Deploy
    {
        if (!$this->fileService->allRequiredFilesExist($target)) {
            return null;
        }

        $setup = $this->setupService->getSetup($releaseOrCommitish, $output);

        return new Deploy($setup, $target);
    }

    /**
     * @return Deploy[]
     *
     * @throws \Exception|Exception
     */
    public function createMany(string $releaseOrCommitish, string $target, OutputInterface $output)
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $instances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($instances)) {
            $output->writeln('For target specification '.$target.' no matching instances were found.');

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
                $setup = $this->setupService->getSetup($releaseOrCommitish, $output);
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
        if (!$deploy instanceof Deploy) {
            $output->writeln('Not a '.Deploy::class);

            return false;
        }

        return true;
    }

    /**
     * @param Deploy $deploy
     *
     * @throws \Exception
     */
    protected function doExecute($deploy, OutputInterface $output)
    {
        $setup = $deploy->getSetup();
        $target = $deploy->getTarget();
        $connection = $target->getConnection();

        $output->writeln('determine target folder');
        $newInstallation = $this->installationService->install($target, $setup);

        $output->writeln('uploading files');
        $this->fileService->uploadFiles($target, $newInstallation);

        $output->writeln('executing deploy hook');
        $this->scriptService->executeDeployHook($output, $target, $newInstallation);

        $output->writeln('switching to new release');
        $this->instanceService->switchInstallation($target, $newInstallation);
        $output->writeln('release online');

        $output->writeln('cleaning old installations if required');
        $this->instanceService->removeOldInstallations($deploy, $connection);

        $output->writeln('executing after deploy hook');
        $this->scriptService->executeAfterDeployHook($output, $target);
    }
}
