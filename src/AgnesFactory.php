<?php

namespace Agnes;

use Agnes\Actions\CopySharedAction;
use Agnes\Actions\DeployAction;
use Agnes\Actions\ReleaseAction;
use Agnes\Actions\RollbackAction;
use Agnes\Commands\CopySharedCommand;
use Agnes\Commands\DeployCommand;
use Agnes\Commands\ReleaseCommand;
use Agnes\Commands\RollbackCommand;
use Agnes\Services\BuildService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Exception;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Symfony\Component\Console\Command\Command;

class AgnesFactory
{
    /**
     * @var BuildService
     */
    private $buildService;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var InstallationService
     */
    private $installationService;

    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * AgnesFactory constructor.
     */
    public function __construct()
    {
        $redirectPlugin = new RedirectPlugin(['preserve_header' => false]);
        $pluginClient = new PluginClient(HttpClientDiscovery::find(), [$redirectPlugin]);

        // construct internal services
        $configurationService = new ConfigurationService();
        $buildService = new BuildService($configurationService);
        $githubService = new GithubService($pluginClient, $configurationService);
        $installationService = new InstallationService();
        $instanceService = new InstanceService($configurationService, $installationService);
        $policyService = new PolicyService($configurationService, $instanceService);

        // set properties
        $this->buildService = $buildService;
        $this->configurationService = $configurationService;
        $this->githubService = $githubService;
        $this->instanceService = $instanceService;
        $this->installationService = $installationService;
        $this->policyService = $policyService;
    }

    /**
     * @throws Exception
     */
    public function addConfig(string $path)
    {
        $this->configurationService->addConfig($path);
    }

    /**
     * @return ReleaseAction
     */
    public function createReleaseAction()
    {
        return new ReleaseAction($this->buildService, $this->policyService, $this->githubService);
    }

    /**
     * @return DeployAction
     */
    public function createDeployAction()
    {
        return new DeployAction($this->buildService, $this->configurationService, $this->policyService, $this->instanceService, $this->installationService, $this->githubService, $this->createReleaseAction());
    }

    /**
     * @return RollbackAction
     */
    public function createRollbackAction()
    {
        return new RollbackAction($this->configurationService, $this->policyService, $this->instanceService);
    }

    /**
     * @return CopySharedAction
     */
    public function createCopySharedAction()
    {
        return new CopySharedAction($this->policyService, $this->configurationService, $this->instanceService);
    }

    /**
     * @return Command[]
     */
    public function getCommands()
    {
        return [
            new ReleaseCommand($this),
            new DeployCommand($this, $this->configurationService, $this->instanceService, $this->githubService),
            new RollbackCommand($this, $this->instanceService),
            new CopySharedCommand($this, $this->instanceService),
        ];
    }

    public function getConfigurationService(): ConfigurationService
    {
        return $this->configurationService;
    }
}
