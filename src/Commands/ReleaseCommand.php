<?php


namespace Agnes\Commands;

use Agnes\Models\Tasks\Task;
use Agnes\Release\CompressionService;
use Agnes\Release\Release;
use Agnes\Release\GithubService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\TaskExecutionService;
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
     * @var TaskExecutionService
     */
    private $taskExecutionService;

    /**
     * @var CompressionService
     */
    private $compressionService;

    /**
     * ReleaseCommand constructor.
     * @param ConfigurationService $configurationService
     * @param GithubService $githubService
     * @param TaskExecutionService $taskExecutionService
     * @param CompressionService $compressionService
     */
    public function __construct(ConfigurationService $configurationService, GithubService $githubService, TaskExecutionService $taskExecutionService, CompressionService $compressionService)
    {
        parent::__construct($configurationService);

        $this->githubService = $githubService;
        $this->taskExecutionService = $taskExecutionService;
        $this->compressionService = $compressionService;
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

        $taskConfig = $this->configurationService->getTaskConfig("release");
        $this->buildRelease($taskConfig, $githubConfig->getRepository(), $release->getTargetCommitish());

        // zip build folder
        $fileName = "release-" . $release->getTagName() . ".zip";
        $filePath = $taskConfig->getWorkingFolder() . "/" . $fileName;
        $this->compressionService->compress($taskConfig->getWorkingFolder(), $filePath);

        $release->setAsset($fileName, "application/zip", file_get_contents($filePath));

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
     * @param Task $taskConfig
     * @param string $repository
     * @param string $targetCommitish
     * @return void
     * @throws \Exception
     */
    private function buildRelease(Task $taskConfig, string $repository, string $targetCommitish)
    {
        $taskConfig->prependCommand("git clone git@github.com:" . $repository . " .");
        $taskConfig->prependCommand("git checkout " . $targetCommitish);
        $taskConfig->prependCommand("rm -rf .git");

        $taskConfig->execute($this->taskExecutionService);
    }
}
