<?php


namespace Agnes\Services;


use Agnes\Models\Task;
use Agnes\Services\Release\Release;
use Http\Client\Exception;

class ReleaseService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * PublishService constructor.
     * @param ConfigurationService $configurationService
     * @param PolicyService $policyService
     * @param GithubService $githubService
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, GithubService $githubService)
    {
        $this->configurationService = $configurationService;
        $this->policyService = $policyService;
        $this->githubService = $githubService;
    }

    /**
     * @param Release $release
     * @throws \Exception
     * @throws Exception
     * @throws Exception
     */
    public function publish(Release $release): void
    {
        $this->policyService->ensureCanRelease($release);

        $content = $this->buildRelease($release);

        $this->githubService->publish($release, $release->getArchiveName(), "application/zip", $content);
    }

    /**
     * @param Release $release
     * @return string
     * @throws \Exception
     */
    private function buildRelease(Release $release): string
    {
        $githubConfig = $this->configurationService->getGithubConfig();
        $scripts = $this->configurationService->getScripts("release");
        $buildPath = $this->configurationService->getBuildPath();

        $task = new Task($buildPath, $scripts);

        // clean & create working directory exists
        $task->addPreCommand("rm -rf " . $task->getWorkingFolder());
        $task->addPreCommand("mkdir -m=0777 -p " . $task->getWorkingFolder());

        // clone repo, checkout correct commit & then remove git folder
        $task->addPreCommand("git clone git@github.com:" . $githubConfig->getRepository() . " .");
        $task->addPreCommand("git checkout " . $release->getCommitish());
        $task->addPreCommand("rm -rf .git");

        // after release has been build, compress it to a single file
        $fileName = $release->getArchiveName();
        $task->addPostCommand("touch $fileName");
        $task->addPostCommand("tar -czvf $fileName --exclude=$fileName .");

        // actually execute the task
        $buildConnection = $this->configurationService->getBuildConnection();
        $buildConnection->executeTask($task);
        $releaseContent = $buildConnection->readFile($fileName);

        return $releaseContent;
    }
}