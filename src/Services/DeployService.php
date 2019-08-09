<?php


namespace Agnes\Services;


use Agnes\Services\Deploy\Deploy;
use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Models\Task;
use Agnes\Services\Github\ReleaseWithAsset;
use Agnes\Services\GithubService;
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
     * @var TaskService
     */
    private $taskService;

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
     * @param TaskService $taskService
     * @param InstanceService $instanceService
     * @param GithubService $githubService
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, TaskService $taskService, InstanceService $instanceService, GithubService $githubService)
    {
        $this->configurationService = $configurationService;
        $this->policyService = $policyService;
        $this->taskService = $taskService;
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
        $task = new Task($releaseFolder, $deployScripts, ["PREVIOUS_RELEASE_PATH" => $previousReleasePath]);
        $connection->executeTask($task, $this->taskService);

        // publish new version
        $this->instanceService->switchRelease($target, $release);

        // clear old releases
        $this->clearOldReleases($deploy, $connection);
    }

    /**
     * @param Deploy $deploy
     * @param Connection $connection
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
            $connection->execute("rm -rf $path");
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
            $sharedFolderSource = $sharedPath . DIRECTORY_SEPARATOR . $sharedFolder;
            $releaseFolderTarget = $releaseFolder . DIRECTORY_SEPARATOR . $sharedFolder;

            // use content of shared folder as template if it is created for the first time
            if (!$connection->checkFolderExists($sharedFolderSource)) {
                $connection->execute("mv $releaseFolderTarget $sharedFolderSource");
            }

            // remove folder if it exists from release path
            $connection->execute("rm -rf $releaseFolderTarget");

            // create symlink from release path to shared path
            $connection->execute("ln -s $sharedFolderSource $releaseFolderTarget");
        }
    }

    /**
     * @param string $releaseFolder
     * @param Connection $connection
     * @param ReleaseWithAsset $release
     * @throws Exception
     * @throws Exception
     */
    private function uploadRelease(string $releaseFolder, Connection $connection, ReleaseWithAsset $release): void
    {
        // make dir for new release
        $commands = $this->taskService->ensureFolderExistsCommands($releaseFolder);
        $connection->execute(...$commands);

        // transfer release packet
        $assetContent = $this->githubService->asset($release->getAssetId());
        $assetPath = $releaseFolder . DIRECTORY_SEPARATOR . $release->getAssetName();
        $connection->writeFile($assetPath, $assetContent);

        // unpack release packet
        $connection->execute("tar -xzf $assetPath $releaseFolder");

        // remove release packet
        $connection->execute("rm $assetPath");
    }
}