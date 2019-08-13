<?php


namespace Agnes;


use Agnes\Commands\CopySharedCommand;
use Agnes\Commands\DeployCommand;
use Agnes\Commands\ReleaseCommand;
use Agnes\Commands\RollbackCommand;
use Agnes\Services\ConfigurationService;
use Agnes\Actions\CopySharedAction;
use Agnes\Actions\DeployAction;
use Agnes\Services\GithubService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Agnes\Actions\ReleaseAction;
use Agnes\Actions\RollbackAction;
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
     * @var ReleaseAction
     */
    private $releaseService;

    /**
     * @var DeployAction
     */
    private $deployService;

    /**
     * @var RollbackAction
     */
    private $rollbackService;

    /**
     * @var CopySharedAction
     */
    private $copySharedService;

    /**
     * @return ReleaseAction
     */
    public function getReleaseService()
    {
        if ($this->releaseService === null) {
            $this->releaseService = new ReleaseAction($this->configurationService, $this->policyService, $this->githubService);
        }

        return $this->releaseService;
    }

    /**
     * @return DeployAction
     */
    public function getDeployService()
    {
        if ($this->deployService === null) {
            $this->deployService = new DeployAction($this->configurationService, $this->policyService, $this->instanceService, $this->githubService);
        }

        return $this->deployService;
    }

    /**
     * @return RollbackAction
     */
    public function getRollbackService()
    {
        if ($this->rollbackService === null) {
            $this->rollbackService = new RollbackAction($this->configurationService, $this->policyService, $this->instanceService);
        }

        return $this->rollbackService;
    }

    /**
     * @return CopySharedAction
     */
    public function getCopySharedService()
    {
        if ($this->copySharedService === null) {
            $this->copySharedService = new CopySharedAction($this->policyService, $this->configurationService, $this->instanceService);
        }

        return $this->copySharedService;
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
}