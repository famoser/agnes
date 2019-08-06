<?php


namespace Agnes\Commands;

use Agnes\Release\Release;
use Agnes\Release\ReleaseService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\TaskExecutionService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class ReleaseCommand extends ConfigurationAwareCommand
{
    /**
     * @var ReleaseService
     */
    private $releaseService;

    /**
     * @var TaskExecutionService
     */
    private $taskExecutionService;

    /**
     * ReleaseCommand constructor.
     * @param ConfigurationService $configurationService
     * @param ReleaseService $releaseService
     * @param TaskExecutionService $taskExecutionService
     */
    public function __construct(ConfigurationService $configurationService, ReleaseService $releaseService, TaskExecutionService $taskExecutionService)
    {
        parent::__construct($configurationService);

        $this->releaseService = $releaseService;
        $this->taskExecutionService = $taskExecutionService;
    }

    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release.')
            ->setHelp('This command compiles & publishes a new release according to the passed configuration.')
            ->addOption("name", "n", InputOption::VALUE_REQUIRED, "name of the release")
            ->addOption("commitish", "n", InputOption::VALUE_REQUIRED, "commit or branch of the release");

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $release = $this->getRelease($input);

        $this->setReleaseAsset($release);

        $this->publishRelease($release);
    }

    /**
     * @param string $folder
     * @param string $zipFile
     */
    private function compress(string $folder, string $zipFile)
    {
        $zipArchive = new ZipArchive();

        if (!$zipArchive->open($zipFile, ZIPARCHIVE::OVERWRITE))
            die("Failed to create archive\n");

        $zipArchive->addGlob($folder . "/**/*");
        if (!$zipArchive->status == ZIPARCHIVE::ER_OK)
            echo "Failed to write files to zip\n";

        $zipArchive->close();
    }

    /**
     * @param InputInterface $input
     * @return Release
     */
    private function getRelease(InputInterface $input): Release
    {
        $name = $input->getOption("name");
        $commitish = $input->getOption("commitish");

        return new Release($name, $commitish);
    }

    /**
     * @param Release $release
     * @throws \Exception
     */
    private function setReleaseAsset(Release $release): void
    {
        $releaseBuildConfig = $this->configurationService->getTaskConfig("release");
        $this->taskExecutionService->execute($releaseBuildConfig);

        $fileName = "release-" . $release->getTagName() . ".zip";
        $filePath = $releaseBuildConfig->getWorkingFolder() . "/" . $fileName;
        $this->compress($releaseBuildConfig->getWorkingFolder(), $filePath);

        $release->setAsset($fileName, "application/zip", file_get_contents($filePath));
    }

    /**
     * @param Release $release
     * @throws Exception
     * @throws \Exception
     */
    private function publishRelease(Release $release): void
    {
        $githubConfig = $this->configurationService->getGithubConfig();
        $this->releaseService->publishRelease($release, $githubConfig);
    }
}
