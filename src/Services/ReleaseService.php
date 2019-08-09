<?php


namespace Agnes\Services;


use Agnes\Models\Tasks\Task;
use Agnes\Services\GithubService;
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
     * @var TaskService
     */
    private $taskService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * PublishService constructor.
     * @param ConfigurationService $configurationService
     * @param PolicyService $policyService
     * @param TaskService $taskService
     * @param GithubService $githubService
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, TaskService $taskService, GithubService $githubService)
    {
        $this->configurationService = $configurationService;
        $this->policyService = $policyService;
        $this->taskService = $taskService;
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

        // clone repo, checkout correct commit & then remove git folder
        $task->addPreCommand("git clone git@github.com:" . $githubConfig->getRepository() . " .");
        $task->addPreCommand("git checkout " . $release->getCommitish());
        $task->addPreCommand("rm -rf .git");

        // after release has been build, compress it to a single folder
        $fileName = $release->getArchiveName();
        $task->addPostCommand("tar -czvf $fileName .");

        // actually execute the task
        $buildConnection = $this->configurationService->getBuildConnection();
        $buildConnection->executeTask($task, $this->taskService);
        $releaseContent = $buildConnection->readFile($fileName);

        return $releaseContent;
    }
}