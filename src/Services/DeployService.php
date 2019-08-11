<?php


namespace Agnes\Services;


use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\Deploy\Deploy;
use Agnes\Services\Github\ReleaseWithAsset;
use Http\Client\Exception;

class DeployService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var PolicyService
     */
    private $policyService;

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
        $this->configurationService = $configurationService;
        $this->policyService = $policyService;
        $this->instanceService = $instanceService;
        $this->githubService = $githubService;
    }

    /**
     * @param Deploy[] $deploys
     * @throws Exception
     * @throws \Exception
     */
    public function deployMultiple(array $deploys): void
    {
        foreach ($deploys as $deploy) {
            $this->deploy($deploy);
        }
    }

    /**
     * @param Deploy $deploy
     * @throws \Exception
     * @throws Exception
     */
    private function deploy(Deploy $deploy)
    {
        // check policies
        if (!$this->policyService->canDeploy($deploy)) {
            return;
        }

        // block if this installation is active
        $installation = $deploy->getTarget()->getInstallation($deploy->getRelease()->getName());
        if ($installation !== null && $installation->isOnline()) {
            return;
        }

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

            $path = $installation->getPath();
            $connection->executeCommand("rm -rf $path");
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
                // use content of current shared folder
                if ($connection->checkFolderExists($releaseFolderSource)) {
                    $connection->executeCommand("mv $releaseFolderSource $sharedFolderTarget");
                } else {
                    $connection->executeCommand("mkdir -m=0777 -p $sharedFolderTarget");
                }
            }

            // ensure directory structure exists
            $connection->executeCommand("mkdir -m=0777 -p $releaseFolderSource");

            // remove folder if it exists from release path
            $connection->executeCommand("rm -rf $releaseFolderSource");

            // create symlink from release path to shared path
            $relativeSharedFolder = $this->getRelativeSymlinkPath($releaseFolderSource, $sharedFolderTarget);
            $connection->executeCommand("ln -s $relativeSharedFolder $releaseFolderSource");
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
        $connection->executeCommands([
            "rm -rf " . $releaseFolder,
            "mkdir -m=0777 -p " . $releaseFolder
        ]);

        // transfer release packet
        $assetContent = $this->githubService->asset($release->getAssetId());
        $assetPath = $releaseFolder . DIRECTORY_SEPARATOR . $release->getAssetName();
        $connection->writeFile($assetPath, $assetContent);

        // unpack release packet
        $connection->executeCommand("tar -xzf $assetPath -C $releaseFolder");

        // remove release packet
        $connection->executeCommand("rm $assetPath");
    }

    /**
     * @param string $source
     * @param string $target
     * @return string
     */
    private function getRelativeSymlinkPath(string $source, string $target)
    {
        $sourceArray = explode(DIRECTORY_SEPARATOR, $source);
        $targetArray = explode(DIRECTORY_SEPARATOR, $target);

        // get count of entries equal for both paths
        $equalEntries = 0;
        while (($sourceArray[$equalEntries] === $targetArray[$equalEntries])) {
            $equalEntries++;
        }

        // if some equal found, then cut how much path we need from the target in the resulting relative path
        if ($equalEntries > 0) {
            $targetArray = array_slice($targetArray, $equalEntries);
        }

        // find out how many levels we need to go back until we can start the relative target path
        $levelsBack = count($sourceArray) - $equalEntries - 1;

        return str_repeat(".." . DIRECTORY_SEPARATOR, $levelsBack) . implode(DIRECTORY_SEPARATOR, $targetArray);
    }
}