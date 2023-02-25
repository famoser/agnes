<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Task;

use Agnes\Models\Task\Build;
use Agnes\Models\Task\Clear;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Download;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;
use Agnes\Models\Task\Run;
use Agnes\Services\ConfigurationService;
use Agnes\Services\FileService;
use Agnes\Services\GithubService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\ScriptService;
use Agnes\Services\Task\ExecutionVisitor\BuildResult;
use Symfony\Component\Console\Style\StyleInterface;

class ExecutionVisitor extends AbstractTaskVisitor
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var InstallationService
     */
    private $installationService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var ScriptService
     */
    private $scriptService;

    /**
     * @var BuildResult|null
     */
    private $buildResult;

    /**
     * ExecutionVisitor constructor.
     */
    public function __construct(StyleInterface $io, ConfigurationService $configurationService, FileService $fileService, GithubService $githubService, InstallationService $installationService, InstanceService $instanceService, ScriptService $scriptService)
    {
        $this->io = $io;
        $this->configurationService = $configurationService;
        $this->fileService = $fileService;
        $this->githubService = $githubService;
        $this->installationService = $installationService;
        $this->instanceService = $instanceService;
        $this->scriptService = $scriptService;
    }

    /**
     * @throws \Exception
     */
    public function visitCopy(Copy $copy): bool
    {
        // does not make sense to copy from itself
        if ($copy->getSource()->equals($copy->getTarget())) {
            $this->io->warning('Skipping '.$copy->describe().' because source and target are same instance.');

            return true;
        }

        $sourceSharedPath = $copy->getSource()->getSharedFolder();
        $targetSharedPath = $copy->getTarget()->getSharedFolder();
        $connection = $copy->getSource()->getConnection();

        $sharedFolders = $this->configurationService->getSharedFolders();
        foreach ($sharedFolders as $sharedFolder) {
            $sourceFolderPath = $sourceSharedPath.DIRECTORY_SEPARATOR.$sharedFolder;
            $targetFolderPath = $targetSharedPath.DIRECTORY_SEPARATOR.$sharedFolder;

            $this->io->text('copying folder '.$sharedFolder);
            $connection->copyFolderContent($sourceFolderPath, $targetFolderPath);
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function visitClear(Clear $clear): bool
    {
        $target = $clear->getTarget();

        $this->io->text('remove folders without installations');
        $this->installationService->removeFoldersWithoutInstallation($target);

        $this->instanceService->removeOldInstallations($target);

        return true;
    }

    /**
     * @throws \Exception
     */
    public function visitDeploy(Deploy $deploy): bool
    {
        $target = $deploy->getTarget();

        $this->io->text('determine target folder');
        $newInstallation = $this->installationService->install($target, $this->buildResult);

        $this->io->text('uploading files');
        $this->fileService->uploadFiles($target, $newInstallation);

        $this->io->text('executing deploy hook');
        $this->scriptService->executeDeployHook($target, $newInstallation);

        $this->io->text('switching to new release');
        $this->instanceService->switchInstallation($target, $newInstallation);
        $this->io->text('release online');

        $this->instanceService->removeOldInstallations($target);

        $this->io->text('executing after deploy hook');
        $this->scriptService->executeAfterDeployHook($target);

        return true;
    }

    /**
     * @throws \Exception
     */
    public function visitRun(Run $run): bool
    {
        $target = $run->getTarget();

        $this->io->text('executing script');
        $this->scriptService->executeScriptByName($target, $target->getCurrentInstallation(), $run->getScript());

        return true;
    }

    public function visitRelease(Release $release): bool
    {
        $this->io->text('publishing release to github');
        $this->githubService->publish($release->getName(), $this->buildResult->getCommitish(), $this->buildResult->getContent());

        return true;
    }

    public function visitRollback(Rollback $rollback): bool
    {
        $instance = $rollback->getTarget();
        $target = $rollback->getInstallation();

        $this->io->text('executing rollback hook');
        $this->scriptService->executeRollbackHook($instance, $target);

        $this->io->text('switching to previous release');
        $this->instanceService->switchInstallation($instance, $target);
        $this->io->text('previous release online');

        $this->io->text('executing after rollback hook');
        $this->scriptService->executeAfterRollbackHook($instance);

        return true;
    }

    public function visitBuild(Build $build): bool
    {
        $connection = $this->configurationService->getBuildConnection();
        $buildPath = $this->configurationService->getBuildPath();

        $this->io->text('cleaning build folder');
        $connection->createOrClearFolder($buildPath);

        $this->io->text('checking out repository');
        $repositoryCloneUrl = $this->configurationService->getRepositoryUrl();
        $hash = $connection->getRepositoryStateAtCommitish($buildPath, $repositoryCloneUrl, $build->getCommitish());

        $this->io->text('executing build script');
        $scripts = $this->scriptService->getBuildHookCommands();
        $connection->executeScript($buildPath, $scripts);

        $this->io->text('compressing build folder');
        $filePath = $connection->compressTarGz($buildPath, 'build.tar.gz');
        $content = $connection->readFile($filePath);

        $this->buildResult = new BuildResult($build->getCommitish(), $hash, $content);

        $this->io->text('removing build folder');
        $connection->removeFolder($buildPath);

        return true;
    }

    public function visitDownload(Download $downloadGithub): bool
    {
        $this->io->text('downloading asset for release '.$downloadGithub->getRelease());
        $content = $this->githubService->downloadAssetForReleaseByReleaseName($downloadGithub->getRelease());
        if (null === $content) {
            return false;
        }

        $this->buildResult = new BuildResult($downloadGithub->getCommitish(), $downloadGithub->getRelease(), $content);

        return true;
    }

    public function buildExists(): bool
    {
        return null !== $this->buildResult;
    }

    public function getBuildResult(): ?BuildResult
    {
        return $this->buildResult;
    }
}
