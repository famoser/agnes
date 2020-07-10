<?php

namespace Agnes\Services\Task;

use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Models\Task\Build;
use Agnes\Models\Task\CopyShared;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Download;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;
use Agnes\Services\FileService;
use Agnes\Services\GithubService;
use Agnes\Services\InstanceService;
use Symfony\Component\Console\Style\StyleInterface;

class TaskFactory
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * TaskCreationService constructor.
     */
    public function __construct(StyleInterface $io, FileService $fileService, GithubService $githubService, InstanceService $instanceService)
    {
        $this->io = $io;
        $this->fileService = $fileService;
        $this->githubService = $githubService;
        $this->instanceService = $instanceService;
    }

    public function createBuild(string $releaseOrCommitish): ?Build
    {
        return new Build($releaseOrCommitish);
    }

    public function createRelease(string $commitish, string $name): ?Release
    {
        return new Release($commitish, $name);
    }

    /**
     * @throws \Exception
     */
    public function createDeploy(string $releaseOrCommitish, Instance $target): ?Deploy
    {
        if (!$this->fileService->allRequiredFilesExist($target)) {
            $this->io->warning('For instance '.$target->describe().' not all required files were found.');

            return null;
        }

        return new Deploy($releaseOrCommitish, $target);
    }

    public function createDownload(string $releaseOrCommitish): ?Download
    {
        $assetId = $this->githubService->getBuildByReleaseName($releaseOrCommitish);
        if (null === $assetId) {
            $this->io->warning('For release '.$releaseOrCommitish.' no release asset was found.');

            return null;
        }

        return new Download($releaseOrCommitish, $assetId);
    }

    public function createRollback(Instance $instance, ?string $rollbackTo, ?string $rollbackFrom): ?Rollback
    {
        $currentInstallation = $instance->getCurrentInstallation();

        // if no installation, can not rollback
        if (null === $currentInstallation) {
            $this->io->warning('No active installation, can not rollback.');

            return null;
        }

        // skip if rollback from does not match
        if (null !== $rollbackFrom && $currentInstallation->getReleaseOrCommitish() !== $rollbackFrom) {
            $this->io->warning('Active installation does not match '.$rollbackFrom.'. skipping...');

            return null;
        }

        // if not target specified, simply take next lower
        $rollbackToMatcher = null;
        if (null !== $rollbackTo) {
            $rollbackToMatcher = function (Installation $installation) use ($rollbackTo) {
                return $installation->getReleaseOrCommitish() === $rollbackTo;
            };
        }

        /** @var Installation|null $upperBoundInstallation */
        $upperBoundInstallation = null;
        foreach ($instance->getInstallations() as $installation) {
            if ($installation->getNumber() < $currentInstallation->getNumber() &&
                (null === $upperBoundInstallation || $upperBoundInstallation->getNumber() < $installation->getNumber()) &&
                (null === $rollbackToMatcher || $rollbackToMatcher($installation))) {
                $upperBoundInstallation = $installation;
            }
        }

        if (null === $upperBoundInstallation) {
            $this->io->warning('For instance '.$instance->describe().' no matching rollback installation was found.');

            return null;
        }

        return new Rollback($instance, $upperBoundInstallation);
    }

    /**
     * @throws \Exception
     */
    public function createCopyShared(Instance $targetInstance, string $sourceStage): ?CopyShared
    {
        $sourceFilter = new Filter([$targetInstance->getServerName()], [$targetInstance->getEnvironmentName()], [$sourceStage]);
        $sourceInstances = $this->instanceService->getInstancesByFilter($sourceFilter);

        if (0 === count($sourceInstances)) {
            $this->io->warning('For instance '.$targetInstance->describe().' no matching source was found.');

            return null;
        }

        return new CopyShared($sourceInstances[0], $targetInstance);
    }
}
