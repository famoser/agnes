<?php

namespace Agnes\Actions;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Filter;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Models\Setup;
use Agnes\Services\BuildService;
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Http\Client\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class DeployAction extends AbstractAction
{
    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var BuildService
     */
    private $buildService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var InstallationService
     */
    private $installationService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var ReleaseAction
     */
    private $releaseAction;

    /**
     * DeployService constructor.
     */
    public function __construct(BuildService $buildService, ConfigurationService $configurationService, PolicyService $policyService, InstanceService $instanceService, InstallationService $installationService, GithubService $githubService, ReleaseAction $releaseAction)
    {
        parent::__construct($policyService);

        $this->buildService = $buildService;
        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
        $this->installationService = $installationService;
        $this->githubService = $githubService;
        $this->releaseAction = $releaseAction;
    }

    /**
     * @return Deploy[]
     *
     * @throws \Exception|Exception
     */
    public function createMany(string $releaseOrCommitish, string $target, ?string $configFolder, bool $skipValidation, OutputInterface $output)
    {
        $filter = Filter::createFromInstanceSpecification($target);
        $instances = $this->instanceService->getInstancesByFilter($filter);
        if (0 === count($instances)) {
            $output->writeln('For target specification '.$target.' no matching instances were found.');

            return [];
        }

        $configuredFiles = $this->configurationService->getFiles();
        $requiredFiles = [];
        $whitelistFiles = [];
        foreach ($configuredFiles as $configuredFile) {
            $configuredFilePath = $configuredFile->getPath();

            $whitelistFiles[] = $configuredFilePath;
            if ($configuredFile->getIsRequired()) {
                $requiredFiles[] = $configuredFilePath;
            }
        }

        $validatedInstances = [];
        foreach ($instances as $instance) {
            $filePaths = [];
            if (null != $configFolder) {
                $filePaths = $this->getFilesPathsForInstance($instance, $configFolder, $whitelistFiles);
            }

            $containedFiles = array_keys($filePaths);
            $missingFiles = array_diff($requiredFiles, $containedFiles);
            if (!empty($missingFiles) && !$skipValidation) {
                $output->writeln('For instance '.$instance->describe().' the following files are missing: '.implode(', ', $missingFiles));
                continue;
            }

            $validatedInstances[] = [$instance, $filePaths]; // used as argument for Deploy __construct
        }

        /** @var Deploy[] $deploys */
        $deploys = [];
        if (count($validatedInstances) > 0) {
            $setup = $this->getSetup($releaseOrCommitish, $output);

            foreach ($validatedInstances as $validatedInstance) {
                $deploys[] = new Deploy($setup, ...$validatedInstance);
            }
        }

        return $deploys;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    private function getSetup(string $releaseOrCommitish, OutputInterface $output): Setup
    {
        $setup = $this->githubService->createSetupByReleaseName($releaseOrCommitish);
        if (null !== $setup) {
            $output->writeln('Using release found on github.');
        } else {
            $output->writeln('No release by that name found on github. Building from commitish...');
            $build = $this->buildService->build($releaseOrCommitish, $output);
            $setup = Setup::fromBuild($build, $releaseOrCommitish);
        }
        $output->writeln('');

        return $setup;
    }

    /**
     * @param string[] $whitelistFiles
     *
     * @return string[]
     */
    private function getFilesPathsForInstance(Instance $instance, string $configFolderPath, array $whitelistFiles): array
    {
        $instanceFolder = $configFolderPath.DIRECTORY_SEPARATOR.
            'servers'.DIRECTORY_SEPARATOR.
            $instance->getServerName().DIRECTORY_SEPARATOR.
            $instance->getEnvironmentName().DIRECTORY_SEPARATOR.
            $instance->getStage();

        if (!is_dir($instanceFolder)) {
            return [];
        }

        $filePaths = $this->getFilesRecursively($instanceFolder);

        $result = [];
        $instanceFolderPrefixLength = strlen($instanceFolder) + 1;
        foreach ($filePaths as $filePath) {
            $key = substr($filePath, $instanceFolderPrefixLength);

            if (in_array($key, $whitelistFiles)) {
                $result[$key] = $filePath;
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getFilesRecursively(string $folder)
    {
        $directoryElements = scandir($folder);

        $result = [];
        foreach ($directoryElements as $key => $value) {
            $path = realpath($folder.DIRECTORY_SEPARATOR.$value);
            if (!is_dir($path)) {
                $result[] = $path;
            } elseif ('.' != $value && '..' != $value) {
                $result = array_merge($result, $this->getFilesRecursively($path));
            }
        }

        return $result;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute().
     *
     * @param Deploy $deploy
     */
    protected function canProcessPayload($deploy, OutputInterface $output): bool
    {
        if (!$deploy instanceof Deploy) {
            $output->writeln('Not a '.Deploy::class);

            return false;
        }

        return true;
    }

    /**
     * @param Deploy $deploy
     *
     * @throws \Exception
     */
    protected function doExecute($deploy, OutputInterface $output)
    {
        $setup = $deploy->getSetup();
        $target = $deploy->getTarget();
        $connection = $target->getConnection();

        $output->writeln('determine target folder');
        $installation = $this->installationService->createInstallation($target, $setup);

        $output->writeln('uploading build to '.$installation->getFolder());
        $this->uploadBuild($installation->getFolder(), $connection, $setup);

        $output->writeln('creating and linking shared folders');
        $this->createAndLinkSharedFolders($connection, $target, $installation->getFolder());

        $output->writeln('uploading files');
        foreach ($deploy->getFilePaths() as $targetPath => $sourcePath) {
            $fullPath = $installation->getFolder().DIRECTORY_SEPARATOR.$targetPath;
            $content = file_get_contents($sourcePath);
            $connection->writeFile($fullPath, $content);
        }

        $output->writeln('executing deploy script');
        $currentInstallation = $deploy->getTarget()->getCurrentInstallation();
        $environment = [];
        $hasPreviousRelease = null !== $currentInstallation;
        $environment['HAS_PREVIOUS_RELEASE'] = $hasPreviousRelease ? 'true' : 'false';
        if ($hasPreviousRelease) {
            $environment['PREVIOUS_RELEASE_PATH'] = $currentInstallation->getFolder();
        }
        $deployScripts = $this->configurationService->getScripts('deploy');
        $connection->executeScript($installation->getFolder(), $deployScripts, $environment);

        $output->writeln('switching to new release');
        $this->instanceService->switchInstallation($target, $installation);
        $output->writeln('release online');

        $output->writeln('cleaning old releases if required');
        $this->clearOldReleases($deploy, $connection);
    }

    /**
     * @throws \Exception
     */
    private function clearOldReleases(Deploy $deploy, Connection $connection)
    {
        $onlineNumber = $deploy->getTarget()->getCurrentInstallation()->getNumber();
        /** @var Installation[] $oldInstallations */
        $oldInstallations = [];
        foreach ($deploy->getTarget()->getInstallations() as $installation) {
            if ($installation->getNumber() < $onlineNumber) {
                $oldInstallations[$installation->getNumber()] = $installation;
            }
        }

        ksort($oldInstallations);

        // remove excess releases
        $releasesToDelete = count($oldInstallations) - $deploy->getTarget()->getServer()->getKeepReleases();
        foreach ($oldInstallations as $installation) {
            if ($releasesToDelete-- <= 0) {
                break;
            }

            $connection->removeFolder($installation->getFolder());
        }
    }

    /**
     * @throws \Exception
     */
    private function createAndLinkSharedFolders(Connection $connection, Instance $target, string $releaseFolder): void
    {
        $instanceSharedFolder = $target->getSharedFolder();
        $installationSharedFolders = $this->configurationService->getSharedFolders();
        foreach ($installationSharedFolders as $sharedFolder) {
            $sharedFolderTarget = $instanceSharedFolder.DIRECTORY_SEPARATOR.$sharedFolder;
            $releaseFolderSource = $releaseFolder.DIRECTORY_SEPARATOR.$sharedFolder;

            // if created for the first time...
            if (!$connection->checkFolderExists($sharedFolderTarget)) {
                $connection->createFolder($sharedFolderTarget);

                // use content of current shared folder
                if ($connection->checkFolderExists($releaseFolderSource)) {
                    $connection->moveFolder($releaseFolderSource, $sharedFolderTarget);
                }
            }

            // ensure directory structure exists
            $connection->createFolder($releaseFolderSource);

            // remove folder to make space for symlink
            $connection->removeFolder($releaseFolderSource);

            // create symlink from release path to shared path
            $connection->createSymlink($releaseFolderSource, $sharedFolderTarget);
        }
    }

    /**
     * @throws \Exception
     */
    private function uploadBuild(string $releaseFolder, Connection $connection, Setup $setup): void
    {
        // make empty dir for new release
        $connection->createOrClearFolder($releaseFolder);

        // transfer release packet
        $assetPath = $releaseFolder.DIRECTORY_SEPARATOR.$setup->getIdentification().'.tar.gz';
        $connection->writeFile($assetPath, $setup->getContent());

        // unpack release packet
        $connection->uncompressTarGz($assetPath, $releaseFolder);

        // remove release packet
        $connection->removeFile($assetPath);
    }
}
