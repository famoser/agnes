<?php


namespace Agnes\Commands;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Tasks\Task;
use Agnes\Release\GithubService;
use Agnes\Release\Release;
use Agnes\Services\ConfigurationService;
use Agnes\Services\FileService;
use Agnes\Services\TaskService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseCommand extends ConfigurationAwareCommand
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
     * @var FileService
     */
    private $fileService;

    /**
     * ReleaseCommand constructor.
     * @param ConfigurationService $configurationService
     * @param GithubService $githubService
     * @param TaskService $taskExecutionService
     * @param FileService $fileService
     */
    public function __construct(ConfigurationService $configurationService, GithubService $githubService, TaskService $taskExecutionService, FileService $fileService)
    {
        parent::__construct($configurationService);

        $this->githubService = $githubService;
        $this->taskExecutionService = $taskExecutionService;
        $this->fileService = $fileService;
    }

    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release.')
            ->setHelp('This command compiles & publishes a new release according to the passed configuration.')
            ->addOption("name", "na", InputOption::VALUE_REQUIRED, "name of the release")
            ->addOption("commitish", "b", InputOption::VALUE_REQUIRED, "branch or commit of the release");

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
        $release = $this->getRelease($input);
        $githubConfig = $this->configurationService->getGithubConfig();

        $buildConnection = $this->configurationService->getBuildConnection();
        $task = $this->configurationService->getTaskConfig("release");
        $releaseFilename = $this->buildRelease($buildConnection, $task, $githubConfig->getRepository(), $release->getTargetCommitish(), $release->getName());

        $content = $buildConnection->readFile($releaseFilename, $this->fileService);
        $release->setAsset($releaseFilename, "application/zip", $content);

        $this->githubService->publish($release, $githubConfig);
    }

    /**
     * @param InputInterface $input
     * @return Release
     */
    private function getRelease(InputInterface $input): Release
    {
        $name = $input->getOption("name");
        $commitish = $input->getOption("commitish");

        return new Release($name, $commitish);
    }

    /**
     * @param Connection $connection
     * @param Task $task
     * @param string $repository
     * @param string $targetCommitish
     * @param string $releaseName
     * @return string
     */
    private function buildRelease(Connection $connection, Task $task, string $repository, string $targetCommitish, string $releaseName): string
    {
        $task->addPreCommand("git clone git@github.com:" . $repository . " .");
        $task->addPreCommand("git checkout " . $targetCommitish);
        $task->addPreCommand("rm -rf .git");

        // compress release folder
        $fileName = "release-" . $releaseName . ".tar.gz";
        $task->addPostCommand("tar -czvf $fileName .");

        $connection->executeTask($task, $this->taskExecutionService);

        return $fileName;
    }
}
