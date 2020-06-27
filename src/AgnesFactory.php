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
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
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
     * @var PolicyService
     */
    private $policyService;

    /**
     * AgnesFactory constructor.
     */
    public function __construct()
    {
        $redirectPlugin = new RedirectPlugin(["preserve_header" => false]);
        $pluginClient = new PluginClient(HttpClientDiscovery::find(), [$redirectPlugin]);

        // construct internal services
        $configurationService = new ConfigurationService();
        $githubService = new GithubService($pluginClient, $configurationService);
        $instanceService = new InstanceService($configurationService);
        $policyService = new PolicyService($configurationService, $instanceService);

        // set properties
        $this->configurationService = $configurationService;
        $this->githubService = $githubService;
        $this->instanceService = $instanceService;
        $this->policyService = $policyService;
    }

    /**
     * @param string $path
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
        return new ReleaseAction($this->configurationService, $this->policyService, $this->githubService);
    }

    /**
     * @return DeployAction
     */
    public function createDeployAction()
    {
        return new DeployAction($this->configurationService, $this->policyService, $this->instanceService, $this->githubService, $this->createReleaseAction());
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
            new CopySharedCommand($this, $this->instanceService)
        ];
    }

    /**
     * @return ConfigurationService
     */
    public function getConfigurationService(): ConfigurationService
    {
        return $this->configurationService;
    }

    /**
     * @return GithubService
     */
    public function getGithubService(): GithubService
    {
        return $this->githubService;
    }

    /**
     * @return InstanceService
     */
    public function getInstanceService(): InstanceService
    {
        return $this->instanceService;
    }

    /**
     * @return PolicyService
     */
    public function getPolicyService(): PolicyService
    {
        return $this->policyService;
    }
}
