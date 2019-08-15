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
     * @throws Exception
     * @throws \Exception
     */
    protected function doExecute($deploy)
    {
        $release = $deploy->getRelease();
        $target = $deploy->getTarget();
        $connection = $target->getConnection();

        $releaseFolder = $this->instanceService->getReleasePath($target, $release);

        $this->uploadRelease($releaseFolder, $connection, $release);

        $this->instanceService->onReleaseInstalled($target, $releaseFolder, $release);

        // create shared folders
        $this->createAndLinkSharedFolders($connection, $target, $releaseFolder);

        // upload files
        foreach ($deploy->getFiles() as $targetPath => $content) {
            $fullPath = $releaseFolder . DIRECTORY_SEPARATOR . $targetPath;
            $connection->writeFile($fullPath, $content);
        }

        // execute deploy task
        $currentInstallation = $deploy->getTarget()->getCurrentInstallation();
        $previousReleasePath = $currentInstallation ? $currentInstallation->getPath() : null;
        $deployScripts = $this->configurationService->getScripts("deploy");
        $connection->executeScript($releaseFolder, $deployScripts, ["PREVIOUS_RELEASE_PATH" => $previousReleasePath]);

        // publish new version
        $this->instanceService->switchRelease($target, $release);

        // clear old releases
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