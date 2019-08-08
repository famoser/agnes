<?php


namespace Agnes\Commands;

use Agnes\Deploy\Deploy;
use Agnes\Models\Tasks\Filter;
use Agnes\Models\Tasks\Instance;
use Agnes\Models\Tasks\Task;
use Agnes\Release\GithubService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\Github\ReleaseWithAsset;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Agnes\Services\TaskService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends ConfigurationAwareCommand
{
    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var TaskService
     */
    private $taskExecutionService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * ReleaseCommand constructor.
     * @param ConfigurationService $configurationService
     * @param GithubService $githubService
     * @param TaskService $taskExecutionService
     * @param InstanceService $instanceService
     * @param PolicyService $policyService
     */
    public function __construct(ConfigurationService $configurationService, GithubService $githubService, TaskService $taskExecutionService, InstanceService $instanceService, PolicyService $policyService)
    {
        parent::__construct($configurationService);

        $this->githubService = $githubService;
        $this->taskExecutionService = $taskExecutionService;
        $this->instanceService = $instanceService;
        $this->policyService = $policyService;
    }

    public function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploy a release to a specific environment.')
            ->setHelp('This command downloads, installs & publishes a release to a specific environment.')
            ->addArgument("files", InputArgument::IS_ARRAY, "the files to deploy. Separate multiple files with a space. The file path is matched against the configured files, and the longest matching path is chosen as a target.")
            ->addOption("name", "na", InputOption::VALUE_REQUIRED, "name of the release")
            ->addOption("server", "se", InputOption::VALUE_OPTIONAL, "the server to install the release on")
            ->addOption("environment", "e", InputOption::VALUE_OPTIONAL, "the environment to install the release on")
            ->addOption("stage", "st", InputOption::VALUE_OPTIONAL, "the stage to install the release on")
            ->addOption("skip_file_validation", "sv", InputOption::VALUE_NONE, "if file validation should be skipped. the application no longer throws if required file is not supplied");

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $targetReleaseName = $input->getOption("name");
        $release = $this->getRelease($targetReleaseName);

        $server = $input->getOption("server");
        $environment = $input->getOption("environment");
        $stage = $input->getOption("stage");
        $instances = $this->getInstances($server, $environment, $stage);

        $inputFiles = $input->getArgument("files");
        $skipValidation = (bool)$input->getOption("skip_file_validation");
        $fileContents = $this->getFileContents($inputFiles, $skipValidation);

        $deploys = [];
        foreach ($instances as $instance) {
            $deploy = new Deploy($release, $instance, $fileContents);
            if ($this->policyService->canDeploy($deploy)) {
                $deploys[] = $deploy;
            }
        }

        foreach ($deploys as $deploy) {
            $currentInstallation = $deploy->getTarget()->getCurrentInstallation();
            if ($currentInstallation === null || $currentInstallation->getRelease()->getName() !== $deploy->getRelease()->getName()) {
                $this->deploy($deploy);
            }
        }
    }

    /**
     * @param string $targetReleaseName
     * @return ReleaseWithAsset
     * @throws Exception
     * @throws \Exception
     */
    private function getRelease(string $targetReleaseName): ReleaseWithAsset
    {
        $releases = $this->githubService->releases();

        foreach ($releases as $release) {
            if ($release->getName() === $targetReleaseName) {
                return $release;
            }
        }

        throw new \Exception("release with name " . $targetReleaseName . " not found.");
    }

    /**
     * @param string|null $server
     * @param string|null $environment
     * @param string|null $stage
     * @return Instance[]
     * @throws \Exception
     */
    private function getInstances(?string $server, ?string $environment, ?string $stage)
    {
        $servers = $server !== null ? [$server] : [];
        $environments = $environment !== null ? [$environment] : [];
        $stages = $stage !== null ? [$stage] : [];
        $filter = new Filter($servers, $environments, $stages);

        return $this->instanceService->getInstances($filter);
    }

    /**
     * @param Deploy $deploy
     * @throws Exception
     * @throws \Exception
     */
    private function deploy(Deploy $deploy)
    {
        $release = $deploy->getRelease();
        $target = $deploy->getTarget();
        $connection = $target->getConnection();

        // make dir for new release
        $releaseFolder = $this->instanceService->getReleasePath($target, $release);
        $currentReleaseSymlink = $this->instanceService->getCurrentReleaseSymlinkPath($target);
        $commands = $this->taskExecutionService->ensureFolderExistsCommands($releaseFolder);
        $connection->executeCommands($commands);

        // transfer release packet
        $assetContent = $this->githubService->asset($release->getAssetId());
        $assetPath = $releaseFolder . DIRECTORY_SEPARATOR . $release->getAssetName();
        $connection->writeFile($assetPath, $assetContent);

        // unpack release packet
        $connection->executeCommands("tar -xzf $assetPath $releaseFolder");

        // remove release packet
        $connection->executeCommands("rm $assetPath");

        // register which release is installed there
        $this->instanceService->onReleaseInstalled($connection, $releaseFolder, $release);

        // create shared folders
        $sharedPath = $this->instanceService->getSharedPath($target);
        $sharedFolders = $this->configurationService->getSharedFolders();
        foreach ($sharedFolders as $sharedFolder) {
            $sharedFolderSource = $sharedPath . DIRECTORY_SEPARATOR . $sharedFolder;
            $releaseFolderTarget = $releaseFolder . DIRECTORY_SEPARATOR . $sharedFolder;

            // use content of shared folder as template if it is created for the first time
            if (!$connection->checkFolderExists($sharedFolderSource)) {
                $connection->executeCommands("mv $releaseFolderTarget $sharedFolderSource");
            }

            // remove folder if it exists from release path
            $connection->executeCommands("rm -rf $releaseFolderTarget");

            // create symlink from release path to shared path
            $connection->executeCommands("ln -s $sharedFolderSource $releaseFolderTarget");
        }

        // submit files
        foreach ($deploy->getFiles() as $targetPath => $content) {
            $fullPath = $releaseFolder . DIRECTORY_SEPARATOR . $targetPath;
            $connection->writeFile($fullPath, $content);
        }

        // execute deploy task
        $deployScripts = $this->configurationService->getScripts("deploy");
        $task = new Task($releaseFolder, $deployScripts);
        $connection->executeTask($task, $this->taskExecutionService);

        // create new symlink
        $tempCurrentReleaseSymlink = $currentReleaseSymlink . "_";
        $connection->executeCommands("ln -s $releaseFolder $tempCurrentReleaseSymlink");

        // switch active release
        $this->instanceService->onReleaseOffline($connection, $currentReleaseSymlink);
        $connection->executeCommands("mv -T $tempCurrentReleaseSymlink $currentReleaseSymlink");
        $this->instanceService->onReleaseOnline($connection, $currentReleaseSymlink);

        /**
         * pending:
         * - clean up old releases
         */
    }

    /**
     * @param array $inputFiles
     * @param bool $skipValidation
     * @return array
     * @throws \Exception
     */
    private function getFileContents(array $inputFiles, bool $skipValidation): array
    {
        $configuredFiles = $this->configurationService->getEditableFiles();
        $fileContents = [];
        foreach ($inputFiles as $file) {
            $matchFound = false;
            foreach ($configuredFiles as $configuredFile) {
                $configuredFilePath = $configuredFile->getPath();
                $matchPosition = stripos($configuredFilePath, $file);

                // we have a match if it starts at the end; so ride.json matches to var/trans/override.json
                $matchSize = strlen($file);
                $matchAtTheEnd = $matchPosition + $matchSize === strlen($configuredFile->getPath());
                if ($matchAtTheEnd) {
                    if (isset($fileContent[$configuredFilePath])) {
                        throw new \Exception("no unique match for $file");
                    }

                    $filePath = $this->configurationService->getBasePath() . DIRECTORY_SEPARATOR . $file;
                    $fileContent = file_get_contents($filePath);
                    $fileContents[$configuredFilePath] = $fileContent;
                    $matchFound = true;
                }
            }

            // add the file content to the mapping
            if (!$matchFound) {
                throw new \Exception("no match found for file $file");
            }
        }

        // ensure all required files have their match
        foreach ($configuredFiles as $configuredFile) {
            $path = $configuredFile->getPath();
            if ($configuredFile->getIsRequired() && !$skipValidation && !isset($fileContents[$path])) {
                throw new \Exception("you must pass a file which matches $path");
            }
        }

        return $fileContents;
    }
}
