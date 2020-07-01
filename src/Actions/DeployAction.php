<?php

namespace Agnes\Actions;

use Agnes\Models\Build;
use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Http\Client\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class DeployAction extends AbstractAction
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var ReleaseAction
     */
    private $releaseAction;

    /**
     * DeployService constructor.
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, InstanceService $instanceService, GithubService $githubService, ReleaseAction $releaseAction)
    {
        parent::__construct($policyService);

        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
        $this->githubService = $githubService;
        $this->releaseAction = $releaseAction;
    }

    /**
     * @return Deploy[]
     *
     * @throws \Exception|Exception
     */
    public function createMany(string $releaseOrCommitish, string $target, ?string $configFolder, bool $skipValidation, OutputInterface $output)
    {
        $build = $this->githubService->findBuild($releaseOrCommitish);
        if (null !== $build) {
            $output->writeln('Using release found on github.');
        } else {
            $output->writeln('Release does not exist on github; trying to build it.');
            $release = $this->releaseAction->tryCreate($releaseOrCommitish);
            $build = $this->releaseAction->buildRelease($release, $output);
        }
        $output->writeln('');

        $instances = $this->instanceService->getInstancesFromInstanceSpecification($target);
        if (0 === count($instances)) {
            $output->writeln('For target specification '.$target.' no matching instances were found.');

            return [];
        }

        $configuredFiles = $this->configurationService->getFiles();
        $requiredFiles = [];
        $whitelistFiles = [];
        foreach ($configuredFiles as $configuredFile) {
            $configuredFilePath = $configuredFile->getPath();

            $whitelistFiles[] = $configuredFilePath;
            if ($configuredFile->getIsRequired()) {
                $requiredFiles[] = $configuredFilePath;
            }
        }

        /** @var Deploy[] $deploys */
        $deploys = [];
        foreach ($instances as $instance) {
            $filePaths = [];
            if (null != $configFolder) {
                $filePaths = $this->getFilesPathsForInstance($instance, $configFolder, $whitelistFiles);
            }

            $containedFiles = array_keys($filePaths);
            $missingFiles = array_diff($requiredFiles, $containedFiles);
            if (!empty($missingFiles) && !$skipValidation) {
                $output->writeln('For instance '.$instance->describe().' the following files are missing: '.implode(', ', $missingFiles));
                continue;
            }

            $deploys[] = new Deploy($build, $instance, $filePaths);
        }

        return $deploys;
    }

    /**
     * @param string[] $whitelistFiles
     *
     * @return string[]
     */
    private function getFilesPathsForInstance(Instance $instance, string $configFolderPath, array $whitelistFiles): array
    {
        $instanceFolder = $configFolderPath.DIRECTORY_SEPARATOR.
            'servers'.DIRECTORY_SEPARATOR.
            $instance->getServerName().DIRECTORY_SEPARATOR.
            $instance->getEnvironmentName().DIRECTORY_SEPARATOR.
            $instance->getStage();

        $filePaths = $this->getFilesRecursively($instanceFolder);

        $result = [];
        $instanceFolderPrefixLength = strlen($instanceFolder) + 1;
        foreach ($filePaths as $filePath) {
            $key = substr($filePath, $instanceFolderPrefixLength);

            if (in_array($key, $whitelistFiles)) {
                $result[$key] = $filePath;
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getFilesRecursively(string $folder)
    {
        $directoryElements = scandir($folder);

        $result = [];
        foreach ($directoryElements as $key => $value) {
            $path = realpath($folder.DIRECTORY_SEPARATOR.$value);
            if (!is_dir($path)) {
                $result[] = $path;
            } elseif ('.' != $value && '..' != $value) {
                $result = array_merge($result, $this->getFilesRecursively($path));
            }
        }

        return $result;
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
     * @throws Exception
     * @throws \Exception
     */
    protected function doExecute($deploy, OutputInterface $output)
    {
        $build = $deploy->getBuild();
        $target = $deploy->getTarget();
        $connection = $target->getConnection();

        $releaseFolder = $this->instanceService->getReleasePath($target, $build);

        $output->writeln('uploading release');
        $this->uploadBuild($releaseFolder, $connection, $build);

        $output->writeln('registering new release');
        $this->instanceService->onReleaseInstalled($target, $releaseFolder, $build);

        $output->writeln('creating and linking shared folders');
        $this->createAndLinkSharedFolders($connection, $target, $releaseFolder);

        $output->writeln('uploading files');
        foreach ($deploy->getFilePaths() as $targetPath => $sourcePath) {
            $fullPath = $releaseFolder.DIRECTORY_SEPARATOR.$targetPath;
            $content = file_get_contents($sourcePath);
            $connection->writeFile($fullPath, $content);
        }

        $output->writeln('executing deploy script');
        $currentInstallation = $deploy->getTarget()->getCurrentInstallation();
        $environment = [];
        $hasPreviousRelease = null !== $currentInstallation;
        $environment['HAS_PREVIOUS_RELEASE'] = $hasPreviousRelease ? 'true' : 'false';
        if ($hasPreviousRelease) {
            $environment['PREVIOUS_RELEASE_PATH'] = $currentInstallation->getPath();
        }
        $deployScripts = $this->configurationService->getScripts('deploy');
        $connection->executeScript($releaseFolder, $deployScripts, $environment);

        $output->writeln('switching to new release');
        $this->instanceService->switchRelease($target, $build);
        $output->writeln('release online');

        $output->writeln('cleaning old releases if required');
        $this->clearOldReleases($deploy, $connection);
    }

    /**
     * @throws \Exception
     */
    private function clearOldReleases(Deploy $deploy, Connection $connection)
    {
        /** @var Installation[] $offlineInstallationsByLastOnlineTimestamp */
        $offlineInstallationsByLastOnlineTimestamp = [];
        foreach ($deploy->getTarget()->getInstallations() as $installation) {
            $lastOnline = $installation->getLastOnline();
            if (null !== $lastOnline && !$installation->isOnline()) {
                $offlineInstallationsByLastOnlineTimestamp[$lastOnline->getTimestamp()] = $installation;
            }
        }

        ksort($offlineInstallationsByLastOnlineTimestamp);

        // remove excess releases
        $releasesToDelete = count($offlineInstallationsByLastOnlineTimestamp) - $deploy->getTarget()->getKeepReleases();
        foreach ($offlineInstallationsByLastOnlineTimestamp as $installation) {
            if ($releasesToDelete-- <= 0) {
                break;
            }

            $connection->removeFolder($installation->getPath());
        }
    }

    /**
     * @throws \Exception
     */
    private function createAndLinkSharedFolders(Connection $connection, Instance $target, string $releaseFolder): void
    {
        $sharedPath = $this->instanceService->getSharedPath($target);
        $sharedFolders = $this->configurationService->getSharedFolders();
        foreach ($sharedFolders as $sharedFolder) {
            $sharedFolderTarget = $sharedPath.DIRECTORY_SEPARATOR.$sharedFolder;
            $releaseFolderSource = $releaseFolder.DIRECTORY_SEPARATOR.$sharedFolder;

            // if created for the first time...
            if (!$connection->checkFolderExists($sharedFolderTarget)) {
                $connection->createFolder($sharedFolderTarget);

                // use content of current shared folder
                if ($connection->checkFolderExists($releaseFolderSource)) {
                    $connection->moveFolder($releaseFolderSource, $sharedFolderTarget);
                }
            }

            // ensure directory structure exists
            $connection->createFolder($releaseFolderSource);

            // remove folder to make space for symlink
            $connection->removeFolder($releaseFolderSource);

            // create symlink from release path to shared path
            $connection->createSymlink($releaseFolderSource, $sharedFolderTarget);
        }
    }

    /**
     * @throws \Exception
     */
    private function uploadBuild(string $releaseFolder, Connection $connection, Build $build): void
    {
        // make empty dir for new release
        $connection->createOrClearFolder($releaseFolder);

        // transfer release packet
        $assetPath = $releaseFolder.DIRECTORY_SEPARATOR.$build->getArchiveName('.tar.gz');
        $connection->writeFile($assetPath, $build->getContent());

        // unpack release packet
        $connection->uncompressTarGz($assetPath, $releaseFolder);

        // remove release packet
        $connection->removeFile($assetPath);
    }
}
