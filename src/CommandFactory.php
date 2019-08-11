<?php


namespace Agnes;


use Agnes\Commands\CopySharedCommand;
use Agnes\Commands\DeployCommand;
use Agnes\Commands\ReleaseCommand;
use Agnes\Commands\RollbackCommand;
use Agnes\Services\ConfigurationService;
use Agnes\Services\CopySharedService;
use Agnes\Services\DeployService;
use Agnes\Services\GithubService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Agnes\Services\ReleaseService;
use Agnes\Services\RollbackService;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
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

        $redirectPlugin = new RedirectPlugin(["preserve_header" => false]);

        $pluginClient = new PluginClient(HttpClientDiscovery::find(), [$redirectPlugin]);
        $githubService = new GithubService($pluginClient, $configurationService);
        $instanceService = new InstanceService($configurationService);
        $policyService = new PolicyService($configurationService, $instanceService);

        $releaseService = new ReleaseService($configurationService, $policyService, $githubService);
        $deployService = new DeployService($configurationService, $policyService, $instanceService, $githubService);
        $rollbackService = new RollbackService($configurationService, $policyService, $instanceService);
        $copySharedService = new CopySharedService($policyService, $configurationService);

        return [
            new ReleaseCommand($configurationService, $releaseService),
            new DeployCommand($configurationService, $deployService, $instanceService, $githubService),
            new RollbackCommand($configurationService, $rollbackService, $instanceService),
            new CopySharedCommand($configurationService, $copySharedService, $instanceService)
        ];
    }
}