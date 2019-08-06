<?php


namespace Agnes\Commands;

use Agnes\Release\Release;
use Agnes\Release\ReleaseService;
use Agnes\Services\Configuration\GithubConfig;
use Agnes\Services\Configuration\TaskConfig;
use Agnes\Services\ConfigurationService;
use Agnes\Services\TaskExecutionService;
use Http\Client\Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
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
            ->addOption("name", "na", InputOption::VALUE_REQUIRED, "name of the release")
            ->addOption("commitish", "b", InputOption::VALUE_REQUIRED, "branch or commit of the release");

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
        $githubConfig = $this->configurationService->getGithubConfig();

        $taskConfig = $this->configurationService->getTaskConfig("release");
        $this->buildRelease($taskConfig, $githubConfig, $release);

        // zip build folder
        $fileName = "release-" . $release->getTagName() . ".zip";
        $filePath = $taskConfig->getWorkingFolder() . "/" . $fileName;
        $this->compress($taskConfig->getWorkingFolder(), $filePath);

        $release->setAsset($fileName, "application/zip", file_get_contents($filePath));

        $this->releaseService->publishRelease($release, $githubConfig);
    }

    /**
     * @param string $folder
     * @param string $zipFile
     * @return bool
     * @throws \Exception
     */
    private function compress(string $folder, string $zipFile)
    {
        $zip = new ZipArchive();

        if (!$zip->open($zipFile, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE)) {
            throw new \Exception("Failed to create archive");
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($folder . '/', '', $file . '/'));
            } else if (is_file($file) === true) {
                $zip->addFile($file, str_replace($folder . '/', '', $file));
            }
        }

        if (!$zip->status == ZIPARCHIVE::ER_OK) {
            throw new \Exception("Failed to write files to zip");
        }

        return $zip->close();
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
     * @param TaskConfig $taskConfig
     * @param GithubConfig $githubConfig
     * @param Release $release
     * @return void
     * @throws \Exception
     */
    private function buildRelease(TaskConfig $taskConfig, GithubConfig $githubConfig, Release $release)
    {
        $taskConfig->prependCommand("git clone git@github.com:" . $githubConfig->getRepository() . " .");
        $taskConfig->prependCommand("git checkout " . $release->getTargetCommitish());
        $taskConfig->prependCommand("rm -rf .git");

        $this->taskExecutionService->execute($taskConfig);
    }
}
