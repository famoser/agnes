<?php


namespace Agnes\Services;


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
        $connection = $this->configurationService->getBuildConnection();
        $buildPath = $this->configurationService->getBuildPath();

        // clean & create working directory exists
        // make empty dir for new release
        $connection->executeCommands([
            "rm -rf " . $buildPath,
            "mkdir -m=0777 -p " . $buildPath
        ]);

        // clone repo, checkout correct commit & then remove git folder
        $githubConfig = $this->configurationService->getGithubConfig();
        $connection->executeScript($buildPath, [
            "git clone git@github.com:" . $githubConfig->getRepository() . " .",
            "git checkout " . $release->getCommitish(),
            "rm -rf .git"
        ]);

        // actually execute the task
        $scripts = $this->configurationService->getScripts("release");
        $connection->executeScript($buildPath, $scripts);

        // after release has been build, compress it to a single file
        $fileName = $release->getArchiveName();
        $connection->executeScript($buildPath, [
            "touch $fileName",
            "tar -czvf $fileName --exclude=$fileName ."
        ]);

        return $connection->readFile($fileName);
    }
}