<?php

namespace Agnes\Actions;

use Agnes\Models\Build;
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\PolicyService;
use Http\Client\Exception;
use Symfony\Component\Console\Output\OutputInterface;

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
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, GithubService $githubService)
    {
        parent::__construct($policyService);

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
        $build = $this->buildRelease($release, $output);

        $output->writeln('publishing release to github');
        $this->githubService->publish($build);
    }

    /**
     * @return Build
     *
     * @throws \Exception
     */
    public function buildRelease(Release $release, OutputInterface $output)
    {
        $connection = $this->configurationService->getBuildConnection();
        $buildPath = $this->configurationService->getBuildPath();

        $output->writeln('cleaning build folder');
        $connection->createOrClearFolder($buildPath);

        $output->writeln('checking out repository');
        $repositoryCloneUrl = $this->configurationService->getRepositoryCloneUrl();
        $gitHash = $connection->checkoutRepository($buildPath, $repositoryCloneUrl, $release->getCommitish());
        $release->setHash($gitHash);

        $output->writeln('executing release script');
        $scripts = $this->configurationService->getScripts('release');
        $connection->executeScript($buildPath, $scripts);

        $output->writeln('compressing build folder');
        $filePath = $connection->compressTarGz($buildPath, $release->getArchiveName('.tar.gz'));
        $content = $connection->readFile($filePath);

        $output->writeln('removing build folder');
        $connection->removeFolder($buildPath);

        return Build::fromRelease($release, $content);
    }
}
