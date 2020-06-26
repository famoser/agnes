<?php


namespace Agnes\Actions;


use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\ConfigurationService;
use Agnes\Services\Github\ReleaseWithAsset;
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
     * DeployService constructor.
     * @param ConfigurationService $configurationService
     * @param PolicyService $policyService
     * @param InstanceService $instanceService
     * @param GithubService $githubService
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, InstanceService $instanceService, GithubService $githubService)
    {
        parent::__construct($policyService);

        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
        $this->githubService = $githubService;
    }

    /**
     * @param string $releaseName
     * @param string $target
     * @param string|null $configFolder
     * @param bool $skipValidation
     * @return Deploy[]
     * @throws Exception
     * @throws \Exception
     */
    public function createMany(string $releaseName, string $target, ?string $configFolder, bool $skipValidation)
    {
        $release = $this->getRelease($releaseName);
        if ($release === null) {
            return [];
        }

        $instances = $this->instanceService->getInstancesFromInstanceSpecification($target);

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
            if ($configFolder != null) {
                $filePaths = $this->getFilesPathsForInstance($instance, $configFolder, $whitelistFiles);
            }

            $valid = true;
            if (!$skipValidation) {
                $containedFiles = array_keys($filePaths);
                $valid = empty(array_diff($requiredFiles, $containedFiles));
            }

            if ($valid) {
                $deploys[] = new Deploy($release, $instance, $filePaths);
            }
        }

        return $deploys;
    }


    /**
     * @param Instance $instance
     * @param string $configFolderPath
     * @param string[] $whitelistFiles
     * @return string[]
     */
    private function getFilesPathsForInstance(Instance $instance, string $configFolderPath, array $whitelistFiles): array
    {
        $instanceFolder = $configFolderPath . DIRECTORY_SEPARATOR .
            "servers" . DIRECTORY_SEPARATOR .
            $instance->getServerName() . DIRECTORY_SEPARATOR .
            $instance->getEnvironmentName() . DIRECTORY_SEPARATOR .
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
     * @param string $folder
     * @return string[]
     */
    private function getFilesRecursively(string $folder)
    {
        $directoryElements = scandir($folder);

        $result = [];
        foreach ($directoryElements as $key => $value) {
            $path = realpath($folder . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $result[] = $path;
            } else if ($value != "." && $value != "..") {
                $result = array_merge($result, $this->getFilesRecursively($path));
            }
        }

        return $result;
    }

    /**
     * @param string $releaseName
     * @return ReleaseWithAsset|null
     * @throws Exception
     */
    private function getRelease(string $releaseName): ?ReleaseWithAsset
    {
        $releases = $this->githubService->releases();

        foreach ($releases as $release) {
            if ($release->getName() === $releaseName) {
                return $release;
            }
        }

        return null;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute()
     *
     * @param Deploy $deploy
     * @return bool
     */
    protected function canProcessPayload($deploy): bool
    {
        if (!$deploy instanceof Deploy) {
            return false;
        }

        // block if this installation is active
        $installation = $deploy->getTarget()->getCurrentInstallation();
        if ($installation !== null && $installation->isSameReleaseName($deploy->getRelease()->getName())) {
            return false;
        }

        return true;
    }

    /**
     * @param Deploy $deploy
     * @param OutputInterface $output
     * @throws Exception
     * @throws \Exception
     */
    protected function doExecute($deploy, OutputInterface $output)
    {
        $release = $deploy->getRelease();
        $target = $deploy->getTarget();
        $connection = $target->getConnection();

        $releaseFolder = $this->instanceService->getReleasePath($target, $release);

        $output->writeln("uploading release");
        $this->uploadRelease($releaseFolder, $connection, $release);

        $output->writeln("registering new release");
        $this->instanceService->onReleaseInstalled($target, $releaseFolder, $release);

        $output->writeln("creating and linking shared folders");
        $this->createAndLinkSharedFolders($connection, $target, $releaseFolder);

        $output->writeln("uploading files");
        foreach ($deploy->getFilePaths() as $targetPath => $sourcePath) {
            $fullPath = $releaseFolder . DIRECTORY_SEPARATOR . $targetPath;
            $content = file_get_contents($sourcePath);
            $connection->writeFile($fullPath, $content);
        }

        $output->writeln("executing deploy script");
        $currentInstallation = $deploy->getTarget()->getCurrentInstallation();
        $environment = [];
        $environment["HAS_PREVIOUS_RELEASE"] = $currentInstallation !== null;
        if ($environment["HAS_PREVIOUS_RELEASE"]) {
            $environment["PREVIOUS_RELEASE_PATH"] = $currentInstallation->getPath();
        }
        $deployScripts = $this->configurationService->getScripts("deploy");
        $connection->executeScript($releaseFolder, $deployScripts, $environment);

        $output->writeln("switching to new release");
        $this->instanceService->switchRelease($target, $release);
        $output->writeln("release online");

        $output->writeln("cleaning old releases if required");
        $this->clearOldReleases($deploy, $connection);
    }

    /**
     * @param Deploy $deploy
     * @param Connection $connection
     * @throws \Exception
     */
    private function clearOldReleases(Deploy $deploy, Connection $connection)
    {
        /** @var Installation[] $offlineInstallationsByLastOnlineTimestamp */
        $offlineInstallationsByLastOnlineTimestamp = [];
        foreach ($deploy->getTarget()->getInstallations() as $installation) {
            $lastOnline = $installation->getLastOnline();
            if ($lastOnline !== null && !$installation->isOnline()) {
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
     * @param Connection $connection
     * @param Instance $target
     * @param string $releaseFolder
     * @throws \Exception
     */
    private function createAndLinkSharedFolders(Connection $connection, Instance $target, string $releaseFolder): void
    {
        $sharedPath = $this->instanceService->getSharedPath($target);
        $sharedFolders = $this->configurationService->getSharedFolders();
        foreach ($sharedFolders as $sharedFolder) {
            $sharedFolderTarget = $sharedPath . DIRECTORY_SEPARATOR . $sharedFolder;
            $releaseFolderSource = $releaseFolder . DIRECTORY_SEPARATOR . $sharedFolder;

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
     * @param string $releaseFolder
     * @param Connection $connection
     * @param ReleaseWithAsset $release
     * @throws Exception
     * @throws \Exception
     */
    private function uploadRelease(string $releaseFolder, Connection $connection, ReleaseWithAsset $release): void
    {
        // make empty dir for new release
        $connection->createOrClearFolder($releaseFolder);

        // transfer release packet
        $assetContent = $this->githubService->asset($release->getAssetId());
        $assetPath = $releaseFolder . DIRECTORY_SEPARATOR . $release->getAssetName();
        $connection->writeFile($assetPath, $assetContent);

        // unpack release packet
        $connection->uncompressTarGz($assetPath, $releaseFolder);

        // remove release packet
        $connection->removeFile($assetPath);
    }
}
