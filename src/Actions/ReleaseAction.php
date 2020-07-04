<?php

namespace Agnes\Actions;

use Agnes\Services\BuildService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\PolicyService;
use Agnes\Services\ScriptService;
use Http\Client\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseAction extends AbstractAction
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
     * @var ScriptService
     */
    private $scriptService;

    /**
     * PublishService constructor.
     */
    public function __construct(BuildService $buildService, ConfigurationService $configurationService, PolicyService $policyService, GithubService $githubService, ScriptService $scriptService)
    {
        parent::__construct($policyService);

        $this->buildService = $buildService;
        $this->configurationService = $configurationService;
        $this->githubService = $githubService;
        $this->scriptService = $scriptService;
    }

    /**
     * @param string $name
     *
     * @return Release
     */
    public function tryCreate(string $commitish, string $name = null)
    {
        return new Release($commitish, $name);
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute().
     *
     * @param Release $payload
     */
    protected function canProcessPayload($payload, OutputInterface $output): bool
    {
    }

    /**
     * @param Release $release
     *
     * @throws Exception
     * @throws \Exception
     */
    protected function doExecute($release, OutputInterface $output)
    {
    }
}
