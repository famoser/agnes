<?php


namespace Agnes;


use Agnes\Commands\DeployCommand;
use Agnes\Commands\ReleaseCommand;
use Agnes\Commands\RollbackCommand;
use Agnes\Release\GithubService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\DeployService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Agnes\Services\ReleaseService;
use Agnes\Services\RollbackService;
use Agnes\Services\TaskService;
use Http\Adapter\Guzzle6\Client;
use Symfony\Component\Console\Command\Command;

class CommandFactory
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * CommandFactory constructor.
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @return Command[]
     */
    public function getCommands()
    {
        $configurationService = new ConfigurationService($this->basePath);
        $client = Client::createWithConfig([]);
        $githubService = new GithubService($client, $configurationService);
        $taskService = new TaskService();
        $instanceService = new InstanceService($configurationService);
        $policyService = new PolicyService($configurationService, $instanceService);

        $releaseService = new ReleaseService($configurationService, $policyService, $taskService, $githubService);
        $deployService = new DeployService($configurationService, $policyService, $taskService, $instanceService, $githubService);
        $rollbackService = new RollbackService($configurationService, $taskService, $instanceService);

        return [
            new ReleaseCommand($configurationService, $releaseService),
            new DeployCommand($configurationService, $deployService, $instanceService, $githubService),
            new RollbackCommand($configurationService, $rollbackService, $instanceService)
        ];
    }
}