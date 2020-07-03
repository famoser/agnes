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
     * @var CopySharedAction
     */
    private $copySharedAction;

    /**
     * @var DeployAction
     */
    private $deployAction;

    /**
     * @var ReleaseAction
     */
    private $releaseAction;

    /**
     * @var RollbackAction
     */
    private $rollbackAction;

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

        // set actions
        $this->releaseAction = new ReleaseAction($this->buildService, $this->configurationService, $this->policyService, $this->githubService, );
        $this->copySharedAction = new CopySharedAction($this->policyService, $this->configurationService, $this->instanceService);
        $this->deployAction = new DeployAction($this->buildService, $this->configurationService, $this->policyService, $this->instanceService, $this->installationService, $this->githubService, $this->releaseAction, );
        $this->rollbackAction = new RollbackAction($this->configurationService, $this->policyService, $this->instanceService, );
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
        return $this->releaseAction;
    }

    /**
     * @return DeployAction
     */
    public function createDeployAction()
    {
        return $this->deployAction;
    }

    /**
     * @return RollbackAction
     */
    public function createRollbackAction()
    {
        return $this->rollbackAction;
    }

    /**
     * @return CopySharedAction
     */
    public function createCopySharedAction()
    {
        return $this->copySharedAction;
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
