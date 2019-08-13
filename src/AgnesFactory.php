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
     * @var ReleaseService
     */
    private $releaseService;

    /**
     * @var DeployService
     */
    private $deployService;

    /**
     * @var RollbackService
     */
    private $rollbackService;

    /**
     * @var CopySharedService
     */
    private $copySharedService;

    /**
     * @return ReleaseService
     */
    public function getReleaseService()
    {
        if ($this->releaseService === null) {
            $this->releaseService = new ReleaseService($this->configurationService, $this->policyService, $this->githubService);
        }

        return $this->releaseService;
    }

    /**
     * @return DeployService
     */
    public function getDeployService()
    {
        if ($this->deployService === null) {
            $this->deployService = new DeployService($this->configurationService, $this->policyService, $this->instanceService, $this->githubService);
        }

        return $this->deployService;
    }

    /**
     * @return RollbackService
     */
    public function getRollbackService()
    {
        if ($this->rollbackService === null) {
            $this->rollbackService = new RollbackService($this->configurationService, $this->policyService, $this->instanceService);
        }

        return $this->rollbackService;
    }

    /**
     * @return CopySharedService
     */
    public function getCopySharedService()
    {
        if ($this->copySharedService === null) {
            $this->copySharedService = new CopySharedService($this->policyService, $this->configurationService, $this->instanceService);
        }

        return $this->copySharedService;
    }

    /**
     * @return Command[]
     */
    public function getCommands()
    {
        return [
            new ReleaseCommand($this->configurationService, $this->getReleaseService()),
            new DeployCommand($this->configurationService, $this->instanceService, $this->githubService, $this->getDeployService()),
            new RollbackCommand($this->configurationService, $this->instanceService, $this->getRollbackService()),
            new CopySharedCommand($this->configurationService, $this->instanceService, $this->getCopySharedService())
        ];
    }
}