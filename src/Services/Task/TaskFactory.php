<?php

namespace Agnes\Services\Task;

use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Models\Task\Build;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Download;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;
use Agnes\Models\Task\Run;
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

    public function createRun(Instance $target, string $script): ?Run
    {
        return new Run($script, $target);
    }

    public function createRelease(string $name): ?Release
    {
        return new Release($name);
    }

    /**
     * @throws \Exception
     */
    public function createDeploy(Instance $target): ?Deploy
    {
        if (!$this->fileService->allRequiredFilesExist($target)) {
            $this->io->warning('For instance '.$target->describe().' not all required files were found.');

            return null;
        }

        return new Deploy($target);
    }

    public function createDownload(string $release): ?Download
    {
        if (!$this->githubService->configured()) {
            return null;
        }

        $commitish = $this->githubService->commitishOfReleaseByReleaseName($release);
        if (null === $commitish) {
            $this->io->warning('Release '.$release.' was not found.');

            return null;
        }

        return new Download($commitish, $release);
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
        if (null !== $rollbackFrom && $currentInstallation->getCommitish() !== $rollbackFrom) {
            $this->io->warning('Active installation does not match '.$rollbackFrom.'. skipping...');

            return null;
        }

        // if not target specified, simply take next lower
        $rollbackToMatcher = null;
        if (null !== $rollbackTo) {
            $rollbackToMatcher = function (Installation $installation) use ($rollbackTo) {
                return $installation->getCommitish() === $rollbackTo;
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
    public function createCopy(Instance $targetInstance, string $sourceStage): ?Copy
    {
        $sourceFilter = new Filter([$targetInstance->getServerName()], [$targetInstance->getEnvironmentName()], [$sourceStage]);
        $sourceInstances = $this->instanceService->getInstancesByFilter($sourceFilter);

        if (0 === count($sourceInstances)) {
            $this->io->warning('For instance '.$targetInstance->describe().' no matching source was found.');

            return null;
        }

        return new Copy($sourceInstances[0], $targetInstance);
    }
}
