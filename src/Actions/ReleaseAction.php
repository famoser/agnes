<?php


namespace Agnes\Actions;


use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\PolicyService;
use Http\Client\Exception;

class ReleaseAction extends AbstractAction
{
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
     * @param ConfigurationService $configurationService
     * @param PolicyService $policyService
     * @param GithubService $githubService
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, GithubService $githubService)
    {
        parent::__construct($policyService);

        $this->configurationService = $configurationService;
        $this->githubService = $githubService;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute()
     *
     * @param Release $payload
     * @return bool
     */
    protected function canProcessPayload($payload): bool
    {
        return $payload instanceof Release;
    }

    /**
     * @param Release $release
     * @throws Exception
     * @throws \Exception
     */
    protected function doExecute($release)
    {
        $content = $this->buildRelease($release);

        $this->githubService->publish($release, $release->getArchiveName(".tar.gz"), "application/zip", $content);
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

        $connection->createOrClearFolder($buildPath);

        $githubConfig = $this->configurationService->getGithubConfig();
        $connection->checkoutRepository($buildPath, $githubConfig->getRepository(), $release->getCommitish());

        $scripts = $this->configurationService->getScripts("release");
        $connection->executeScript($buildPath, $scripts);

        // after release has been build, compress it to a single file
        $filePath = $connection->compressTarGz($buildPath, $release->getArchiveName(".tar.gz"));
        return $connection->readFile($filePath);
    }
}