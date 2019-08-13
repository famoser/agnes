<?php


namespace Agnes\Commands;

use Agnes\Services\ConfigurationService;
use Agnes\Services\Deploy\Deploy;
use Agnes\Services\DeployService;
use Agnes\Services\Github\ReleaseWithAsset;
use Agnes\Services\GithubService;
use Agnes\Services\InstanceService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends ConfigurationAwareCommand
{
    const INSTANCE_SPECIFICATION_EXPLANATION = "
            Instances are specified in the form server:environment:stage (like aws:example.com:production deploys to production of example.com on the aws server). 
            Replace entries with stars to not enforce a constraint (like *:*:production would deploy to all production stages).
            Separate entries with comma (,) to enforce an OR constraint (like *:*:staging,production would deploy to all staging & production instances).";

    /**
     * @var DeployService
     */
    private $deployService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * DeployCommand constructor.
     * @param ConfigurationService $configurationService
     * @param InstanceService $instanceService
     * @param GithubService $githubService
     * @param DeployService $deployService
     */
    public function __construct(ConfigurationService $configurationService, InstanceService $instanceService, GithubService $githubService, DeployService $deployService)
    {
        parent::__construct($configurationService);

        $this->deployService = $deployService;
        $this->instanceService = $instanceService;
        $this->githubService = $githubService;
    }

    public function configure()
    {
        $this->setName('deploy')
            ->setDescription('Deploy a release to a specific environment.')
            ->setHelp('This command downloads, installs & publishes a release to a specific environment.')
            ->addArgument("release", InputArgument::REQUIRED, "name of the release")
            ->addArgument("target", InputArgument::REQUIRED, "the instance(s) to deploy to. " . DeployCommand::INSTANCE_SPECIFICATION_EXPLANATION)
            ->addArgument("files", InputArgument::IS_ARRAY, "the files to deploy. Separate multiple files with a space. The file path is matched against the configured files, and the longest matching path is chosen as a target.")
            ->addOption("skip-file-validation", "sfv", InputOption::VALUE_NONE, "if file validation should be skipped. the application no longer throws if required file is not supplied.");

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
        $releaseName = $input->getArgument("release");
        $release = $this->getRelease($releaseName);

        $target = $input->getArgument("target");
        $instances = $this->instanceService->getInstancesFromInstanceSpecification($target);

        $inputFiles = $input->getArgument("files");
        $skipValidation = (bool)$input->getOption("skip-file-validation");
        $fileContents = $this->getFileContents($inputFiles, !$skipValidation);

        /** @var Deploy[] $deploys */
        $deploys = [];
        foreach ($instances as $instance) {
            $deploys[] = new Deploy($release, $instance, $fileContents);
        }

        $this->deployService->deployMultiple($deploys);
    }

    /**
     * @param string $targetReleaseName
     * @return ReleaseWithAsset
     * @throws \Exception
     * @throws Exception
     * @throws Exception
     */
    private function getRelease(string $targetReleaseName): ReleaseWithAsset
    {
        $releases = $this->githubService->releases();

        foreach ($releases as $release) {
            if ($release->getName() === $targetReleaseName) {
                return $release;
            }
        }

        throw new \Exception("release with name " . $targetReleaseName . " not found.");
    }

    /**
     * @param array $inputFiles
     * @param bool $validate
     * @return array
     * @throws \Exception
     */
    private function getFileContents(array $inputFiles, bool $validate): array
    {
        $configuredFiles = $this->configurationService->getEditableFiles();
        $fileContents = [];
        foreach ($configuredFiles as $configuredFile) {
            $configuredFilePath = $configuredFile->getPath();

            $highestMatch = null;
            $highestMatchSize = 0;
            foreach ($inputFiles as $inputFile) {
                $matchSize = $this->getMatchingSizeFromEnd($configuredFilePath, $inputFile);
                if ($matchSize > $highestMatchSize) {
                    $highestMatchSize = $matchSize;
                    $highestMatch = $inputFile;
                }
            }

            // add the file content to the mapping
            if ($highestMatch === null) {
                if ($configuredFile->getIsRequired() && $validate) {
                    throw new \Exception("no match found for file " . $configuredFile->getPath());
                }
            } else {
                $fileContent = file_get_contents($highestMatch);
                $fileContents[$configuredFilePath] = $fileContent;

                $indexOfFile = array_search($highestMatch, $inputFiles);
                unset($inputFiles[$indexOfFile]);
            }
        }

        if (count($inputFiles) > 0) {
            throw new \Exception("the file(s) " . implode($inputFiles) . " have no match");
        }

        return $fileContents;
    }

    /**
     * @param string $string1
     * @param string $string2
     * @return int
     */
    private function getMatchingSizeFromEnd(string $string1, string $string2)
    {
        $sizeString1 = strlen($string1);
        $sizeString2 = strlen($string2);

        $minSize = min($sizeString1, $sizeString2);

        for ($i = 0; $i < $minSize; $i++) {
            if ($string1[$sizeString1 - $i - 1] !== $string2[$sizeString2 - $i - 1]) {
                return $i;
            }
        }

        return $minSize - 1;
    }
}
