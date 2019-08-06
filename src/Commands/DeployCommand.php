<?php


namespace Agnes\Commands;

use Agnes\Release\CompressionService;
use Agnes\Release\Release;
use Agnes\Release\GithubService;
use Agnes\Services\Configuration\GithubConfig;
use Agnes\Services\Configuration\TaskConfig;
use Agnes\Services\ConfigurationService;
use Agnes\Services\TaskExecutionService;
use Http\Client\Exception;
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
        $this->setName('deploy')
            ->setDescription('Deploy a release to a specific environment.')
            ->setHelp('This command downloads, installs & publishes a release to a specific environment.')
            ->addOption("name", "na", InputOption::VALUE_REQUIRED, "name of the release")
            ->addOption("stage", "st", InputOption::VALUE_OPTIONAL, "the stage to install the release on")
            ->addOption("environment", "e", InputOption::VALUE_OPTIONAL, "the environment to install the release on")
            ->addOption("server", "se", InputOption::VALUE_OPTIONAL, "the server to install the release on");

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
        $githubConfig = $this->configurationService->getGithubConfig();

        $targetReleaseName = $input->getOption("name");
        $release = $this->getRelease($targetReleaseName, $githubConfig);
    }

    /**
     * @param string $targetReleaseName
     * @param GithubConfig $githubConfig
     * @return \Agnes\Services\Github\Release
     * @throws Exception
     * @throws \Exception
     */
    private function getRelease(string $targetReleaseName, GithubConfig $githubConfig): \Agnes\Services\Github\Release
    {
        $releases = $this->githubService->releases($githubConfig);

        foreach ($releases as $release) {
            if ($release->getName() === $targetReleaseName) {
                return $release;
            }
        }

        throw new \Exception("release with name " . $targetReleaseName . " not found.");
    }
}
