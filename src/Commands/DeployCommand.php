<?php


namespace Agnes\Commands;

use Agnes\Deploy\Deploy;
use Agnes\Models\Tasks\Filter;
use Agnes\Models\Tasks\Instance;
use Agnes\Release\GithubService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\Github\ReleaseWithAsset;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Agnes\Services\TaskService;
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
            ->addOption("name", "na", InputOption::VALUE_REQUIRED, "name of the release")
            ->addOption("server", "se", InputOption::VALUE_OPTIONAL, "the server to install the release on")
            ->addOption("environment", "e", InputOption::VALUE_OPTIONAL, "the environment to install the release on")
            ->addOption("stage", "st", InputOption::VALUE_OPTIONAL, "the stage to install the release on");

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

        $deploys = [];
        foreach ($instances as $instance) {
            $deploy = new Deploy($release, $instance);
            if ($this->policyService->canDeploy($deploy)) {
                $deploys[] = $deploy;
            }
        }

        foreach ($deploys as $deploy) {
            $this->deploy($deploy);
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
        $assetContent = $this->githubService->asset($deploy->getRelease()->getAssetId());

        $deployTask = $this->configurationService->getTask("deploy");
        /**
         * process:
         * - make new dir for release
         * - make dir with release
         * - transfer archive
         * - uncompress archive
         * - delete archive
         * - create .agnes file
         * - link shared folder
         * - create env file
         * . fill env file (with what???)
         * - execute deploy tasks
         * - create atomic symlink https://unix.stackexchange.com/a/6786/278058
         * - edit .agnes file with release time
         */
        $deployTask->addPreCommand("mkdir");

        $deploy->getTarget()->getConnection()->executeTask($deployTask, $this->taskExecutionService);
    }
}
