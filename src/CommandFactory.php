<?php


namespace Agnes;


use Agnes\Commands\DeployCommand;
use Agnes\Commands\ReleaseCommand;
use Agnes\Release\GithubService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
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
        $taskExecutionService = new TaskService();
        $instanceService = new InstanceService($configurationService);
        $policyService = new PolicyService($configurationService, $instanceService);

        return [
            new ReleaseCommand($configurationService, $policyService, $githubService, $taskExecutionService),
            new DeployCommand($configurationService, $githubService, $taskExecutionService, $instanceService, $policyService)
        ];
    }
}