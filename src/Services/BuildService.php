<?php

namespace Agnes\Services;

use Agnes\Models\Build;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

class BuildService
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var StyleInterface
     */
    private $io;

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
    public function build(string $committish, array $buildScript)
    {
        $connection = $this->configurationService->getBuildConnection();
        $buildPath = $this->configurationService->getBuildPath();

        $this->io->text('cleaning build folder');
        $connection->createOrClearFolder($buildPath);

        $this->io->text('checking out repository');
        $repositoryCloneUrl = $this->configurationService->getRepositoryCloneUrl();
        $gitHash = $connection->checkoutRepository($buildPath, $repositoryCloneUrl, $committish);

        $this->io->text('executing release script');
        $connection->executeScript($buildPath, $buildScript);

        $this->io->text('compressing build folder');
        $filePath = $connection->compressTarGz($buildPath, 'build..tar.gz');
        $content = $connection->readFile($filePath);

        $this->io->text('removing build folder');
        $connection->removeFolder($buildPath);

        return new Build($committish, $gitHash, $content);
    }
}
