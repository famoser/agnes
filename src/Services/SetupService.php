<?php

namespace Agnes\Services;

use Agnes\Models\Setup;
use Http\Client\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class SetupService
{
    /**
     * @var BuildService
     */
    private $buildService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var ScriptService
     */
    private $scriptService;

    /**
     * SetupService constructor.
     */
    public function __construct(BuildService $buildService, GithubService $githubService, ScriptService $scriptService)
    {
        $this->buildService = $buildService;
        $this->githubService = $githubService;
        $this->scriptService = $scriptService;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function getSetup(string $releaseOrCommitish, OutputInterface $output): Setup
    {
        $setup = $this->githubService->createSetupByReleaseName($releaseOrCommitish);
        if (null !== $setup) {
            $output->writeln('Using release found on github.');
        } else {
            $output->writeln('No release by that name found on github. Building from commitish...');
            $scripts = $this->scriptService->getBuildHookCommands();
            $build = $this->buildService->build($releaseOrCommitish, $scripts, $output);
            $setup = Setup::fromBuild($build, $releaseOrCommitish);
        }
        $output->writeln('');

        return $setup;
    }
}
