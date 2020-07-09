<?php

namespace Agnes\Services;

use Agnes\Models\Setup;
use Http\Client\Exception;
use Symfony\Component\Console\Style\StyleInterface;

class SetupService
{
    /**
     * @var BuildRepository
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
    public function __construct(BuildRepository $buildService, GithubService $githubService, ScriptService $scriptService)
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
    }
}
