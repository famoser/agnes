<?php

namespace Agnes\Actions;

use Agnes\Services\BuildService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\PolicyService;
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
     * PublishService constructor.
     */
    public function __construct(BuildService $buildService, ConfigurationService $configurationService, PolicyService $policyService, GithubService $githubService)
    {
        parent::__construct($policyService);

        $this->buildService = $buildService;
        $this->configurationService = $configurationService;
        $this->githubService = $githubService;
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
        if (!$payload instanceof Release) {
            $output->writeln('Not a '.Release::class);

            return false;
        }

        return true;
    }

    /**
     * @param Release $release
     *
     * @throws Exception
     * @throws \Exception
     */
    protected function doExecute($release, OutputInterface $output)
    {
        $scripts = $this->getBuildHookCommands($output);
        $build = $this->buildService->build($release->getCommitish(), $scripts, $output);

        $output->writeln('publishing release to github');
        $this->githubService->publish($release->getName(), $build);
    }
}
