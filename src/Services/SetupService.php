<?php

namespace Agnes\Services;

use Agnes\Models\Setup;
use Http\Client\Exception;
use Symfony\Component\Console\Style\StyleInterface;

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
     * @var StyleInterface
     */
    private $io;

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
    public function getSetup(string $releaseOrCommitish): Setup
    {
        $setup = $this->githubService->createSetupByReleaseName($releaseOrCommitish);
        if (null !== $setup) {
            $this->io->text('Using release found on github.');
        } else {
            $this->io->text('No release by that name found on github. Building from commitish...');
            $scripts = $this->scriptService->getBuildHookCommands();
            $build = $this->buildService->build($releaseOrCommitish, $scripts);
            $setup = Setup::fromBuild($build, $releaseOrCommitish);
        }

        return $setup;
    }
}
