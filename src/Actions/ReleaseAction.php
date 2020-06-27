<?php


namespace Agnes\Actions;


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
     * @param string $name
     * @param string $commitish
     * @param string|null $body
     * @return Release
     */
    public function tryCreate(string $name, string $commitish)
    {
        return new Release($name, $commitish);
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
     * @param OutputInterface $output
     * @throws Exception
     * @throws \Exception
     */
    protected function doExecute($release, OutputInterface $output)
    {
        $connection = $this->configurationService->getBuildConnection();
        $buildPath = $this->configurationService->getBuildPath();

        $output->writeln("cleaning build folder");
        $connection->createOrClearFolder($buildPath);

        $output->writeln("checking out repository");
        $githubConfig = $this->configurationService->getGithubConfig();
        $connection->checkoutRepository($buildPath, $githubConfig->getRepository(), $release->getCommitish());

        $output->writeln("executing release script");
        $scripts = $this->configurationService->getScripts("release");
        $connection->executeScript($buildPath, $scripts);

        $output->writeln("compressing build folder");
        $filePath = $connection->compressTarGz($buildPath, $release->getArchiveName(".tar.gz"));
        $content = $connection->readFile($filePath);

        $output->writeln("publishing release to github");
        $this->githubService->publish($release, $content, "application/zip", $release->getArchiveName(".tar.gz"));

        $output->writeln("removing build folder");
        $connection->removeFolder($buildPath);
    }
}
