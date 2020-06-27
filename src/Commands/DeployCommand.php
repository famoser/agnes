<?php


namespace Agnes\Commands;

use Agnes\Actions\AbstractAction;
use Agnes\Actions\AbstractPayload;
use Agnes\Actions\DeployAction;
use Agnes\AgnesFactory;
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\InstanceService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends AgnesCommand
{
    const INSTANCE_SPECIFICATION_EXPLANATION = "
            Instances are specified in the form server:environment:stage (like aws:example.com:production deploys to production of example.com on the aws server). 
            Replace entries with stars to not enforce a constraint (like *:*:production would deploy to all production stages).
            Separate entries with comma (,) to enforce an OR constraint (like *:*:staging,production would deploy to all staging & production instances).";

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
     * DeployCommand constructor.
     * @param AgnesFactory $factory
     * @param ConfigurationService $configurationService
     * @param InstanceService $instanceService
     * @param GithubService $githubService
     */
    public function __construct(AgnesFactory $factory, ConfigurationService $configurationService, InstanceService $instanceService, GithubService $githubService)
    {
        parent::__construct($factory);

        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
        $this->githubService = $githubService;
    }

    public function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploy a release to a specific environment')
            ->setHelp('This command installs a release to a specific environment and if the installation succeeds, it publishes it.')
            ->addArgument("release or commitish", InputArgument::REQUIRED, "name of the (github) release or commitish")
            ->addArgument("target", InputArgument::REQUIRED, "the instance(s) to deploy to. " . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addOption("skip-file-validation", null, InputOption::VALUE_NONE, "if file validation should be skipped. the application no longer throws if a required file is not supplied.");

        parent::configure();
    }

    /**
     * @param AgnesFactory $factory
     * @return AbstractAction
     */
    protected function getAction(AgnesFactory $factory): AbstractAction
    {
        return $factory->createDeployAction();
    }

    /**
     * @param AbstractAction $action
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return AbstractPayload[]
     * @throws \Exception
     */
    protected function createPayloads(AbstractAction $action, InputInterface $input, OutputInterface $output): array
    {
        $releaseOrCommitish = $input->getArgument("release or commitish");
        $target = $input->getArgument("target");
        $configFolder = $this->getConfigFolder();
        $skipValidation = (bool)$input->getOption("skip-file-validation");

        /** @var DeployAction $action */
        return $action->createMany($releaseOrCommitish, $target, $configFolder, $skipValidation, $output);
    }
}
