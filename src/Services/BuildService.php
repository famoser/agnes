<?php

namespace Agnes\Services;

use Agnes\Models\Build;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class BuildService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * PublishService constructor.
     */
    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @return Build
     *
     * @throws Exception
     */
    public function build(string $committish, array $buildScript, OutputInterface $output)
    {
        $connection = $this->configurationService->getBuildConnection();
        $buildPath = $this->configurationService->getBuildPath();

        $output->writeln('cleaning build folder');
        $connection->createOrClearFolder($buildPath);

        $output->writeln('checking out repository');
        $repositoryCloneUrl = $this->configurationService->getRepositoryCloneUrl();
        $gitHash = $connection->checkoutRepository($buildPath, $repositoryCloneUrl, $committish);

        $output->writeln('executing release script');
        $connection->executeScript($buildPath, $buildScript);

        $output->writeln('compressing build folder');
        $filePath = $connection->compressTarGz($buildPath, 'build..tar.gz');
        $content = $connection->readFile($filePath);

        $output->writeln('removing build folder');
        $connection->removeFolder($buildPath);

        return new Build($committish, $gitHash, $content);
    }
}
