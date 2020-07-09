<?php

namespace Agnes;

use Agnes\Services\ConfigurationService;
use Agnes\Services\FileService;
use Agnes\Services\GithubService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Agnes\Services\ScriptService;
use Agnes\Services\TaskService;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Symfony\Component\Console\Style\StyleInterface;

class AgnesFactory
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * AgnesFactory constructor.
     */
    public function __construct(StyleInterface $io)
    {
        $redirectPlugin = new RedirectPlugin(['preserve_header' => false]);
        $pluginClient = new PluginClient(HttpClientDiscovery::find(), [$redirectPlugin]);

        // construct internal services
        $configurationService = new ConfigurationService($io);
        $fileService = new FileService($io, $configurationService);
        $githubService = new GithubService($pluginClient, $configurationService);
        $installationService = new InstallationService($io, $configurationService);
        $instanceService = new InstanceService($configurationService, $installationService);
        $policyService = new PolicyService($configurationService, $instanceService, $io);
        $scriptService = new ScriptService($io, $configurationService);
        $taskService = new TaskService($io, $configurationService, $fileService, $githubService, $installationService, $instanceService, $scriptService);

        // set properties
        $this->taskService = $taskService;
    }

    public function getConfigurationService(): ConfigurationService
    {
        return $this->configurationService;
    }

    public function getTaskService()
    {
        return $this->taskService;
    }
}
