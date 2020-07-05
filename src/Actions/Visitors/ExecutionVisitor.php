<?php

namespace Agnes\Actions\Visitors;

use Agnes\Actions\CopyShared;
use Agnes\Actions\Deploy;
use Agnes\Actions\Release;
use Agnes\Actions\Rollback;
use Agnes\Models\Build;
use Agnes\Models\Setup;
use Agnes\Services\ConfigurationService;
use Agnes\Services\FileService;
use Agnes\Services\GithubService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\ScriptService;
use Symfony\Component\Console\Style\StyleInterface;

class ExecutionVisitor extends AbstractActionVisitor
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
    public function visitCopyShared(CopyShared $copyShared): bool
    {
        $sourceSharedPath = $copyShared->getSource()->getSharedFolder();
        $targetSharedPath = $copyShared->getTarget()->getSharedFolder();
        $connection = $copyShared->getSource()->getConnection();

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
    public function visitDeploy(Deploy $deploy): bool
    {
        $setup = $deploy->getSetup();
        $target = $deploy->getTarget();
        $connection = $target->getConnection();

        $this->io->text('determine target folder');
        $newInstallation = $this->installationService->install($target, $setup);

        $this->io->text('uploading files');
        $this->fileService->uploadFiles($target, $newInstallation);

        $this->io->text('executing deploy hook');
        $this->scriptService->executeDeployHook($target, $newInstallation);

        $this->io->text('switching to new release');
        $this->instanceService->switchInstallation($target, $newInstallation);
        $this->io->text('release online');

        $this->io->text('cleaning old installations if required');
        $this->instanceService->removeOldInstallations($deploy, $connection);

        $this->io->text('executing after deploy hook');
        $this->scriptService->executeAfterDeployHook($target);

        return true;
    }

    /**
     * @throws \Exception|\Http\Client\Exception
     */
    public function visitRelease(Release $release): bool
    {
        $build = $this->createBuild($release->getCommitish());

        $this->io->text('publishing release to github');
        $this->githubService->publish($release->getName(), $build);

        return true;
    }

    /**
     * @throws \Exception
     */
    public function visitRollback(Rollback $rollback): bool
    {
        $instance = $rollback->getInstance();
        $target = $rollback->getTarget();

        $this->io->text('executing rollback hook');
        $this->scriptService->executeRollbackHook($instance, $target);

        $this->io->text('switching to previous release');
        $this->instanceService->switchInstallation($instance, $target);
        $this->io->text('previous release online');

        $this->io->text('executing after rollback hook');
        $this->scriptService->executeAfterRollbackHook($instance);

        return true;
    }

    /**
     * @throws \Exception
     */
    public function createBuild(string $committish): Build
    {
        $connection = $this->configurationService->getBuildConnection();
        $buildPath = $this->configurationService->getBuildPath();

        $this->io->text('cleaning build folder');
        $connection->createOrClearFolder($buildPath);

        $this->io->text('checking out repository');
        $repositoryCloneUrl = $this->configurationService->getRepositoryCloneUrl();
        $gitHash = $connection->checkoutRepository($buildPath, $repositoryCloneUrl, $committish);

        $this->io->text('executing release script');
        $scripts = $this->scriptService->getBuildHookCommands();
        $connection->executeScript($buildPath, $scripts);

        $this->io->text('compressing build folder');
        $filePath = $connection->compressTarGz($buildPath, 'build..tar.gz');
        $content = $connection->readFile($filePath);

        $this->io->text('removing build folder');
        $connection->removeFolder($buildPath);

        return new Build($committish, $gitHash, $content);
    }

    /**
     * @throws \Http\Client\Exception
     */
    public function createSetup(string $releaseOrCommitish): Setup
    {
        $setup = $this->githubService->createSetupByReleaseName($releaseOrCommitish);
        if (null !== $setup) {
            $this->io->text('Using release found on github.');
        } else {
            $this->io->text('No release by that name found on github. Building from commitish...');
            $build = $this->createBuild($releaseOrCommitish);
            $setup = Setup::fromBuild($build, $releaseOrCommitish);
        }

        return $setup;
    }
}
