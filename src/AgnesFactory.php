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
use Agnes\Services\ScriptService;
use Exception;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\StyleInterface;

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
    public function __construct(StyleInterface $io)
    {
        $redirectPlugin = new RedirectPlugin(['preserve_header' => false]);
        $pluginClient = new PluginClient(HttpClientDiscovery::find(), [$redirectPlugin]);

        // construct internal services
        $configurationService = new ConfigurationService();
        $buildService = new BuildService($configurationService);
        $githubService = new GithubService($pluginClient, $configurationService);
        $installationService = new InstallationService(,);
        $instanceService = new InstanceService($configurationService, $installationService);
        $policyService = new PolicyService($configurationService, $instanceService);
        $scriptService = new ScriptService($configurationService, $this);

        // set properties
        $this->buildService = $buildService;
        $this->configurationService = $configurationService;
        $this->githubService = $githubService;
        $this->instanceService = $instanceService;
        $this->installationService = $installationService;
        $this->policyService = $policyService;
        $this->scriptService = $scriptService;

        // set actions
        $this->releaseAction = new ReleaseAction($this->buildService, $this->configurationService, $this->policyService, $this->githubService, $this->scriptService);
        $this->copySharedAction = new CopySharedAction($this->policyService, $this->configurationService, $this->instanceService);
        $this->deployAction = new DeployAction($this->buildService, $this->configurationService, $this->policyService, $this->instanceService, $this->installationService, $this->githubService, $this->releaseAction, $this->scriptService);
        $this->rollbackAction = new RollbackAction($this->configurationService, $this->policyService, $this->instanceService, $this->scriptService);
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
    public function getReleaseAction()
    {
        return $this->releaseAction;
    }

    /**
     * @return DeployAction
     */
    public function getDeployAction()
    {
        return $this->deployAction;
    }

    /**
     * @return RollbackAction
     */
    public function getRollbackAction()
    {
        return $this->rollbackAction;
    }

    /**
     * @return CopySharedAction
     */
    public function getCopySharedAction()
    {
        return $this->copySharedAction;
    }

    /**
     * @return Command[]
     */
    public function getCommands()
    {
        return [
            new ReleaseCommand(),
            new DeployCommand(),
            new RollbackCommand(),
            new CopySharedCommand($this, $this->instanceService),
        ];
    }

    public function getConfigurationService(): ConfigurationService
    {
        return $this->configurationService;
    }
}
