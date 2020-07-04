<?php

namespace Agnes\Services;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Models\Setup;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

class InstallationService
{
    const AGNES_FILE_NAME = '.agnes';

    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * InstallationService constructor.
     */
    public function __construct(StyleInterface $io, ConfigurationService $configurationService)
    {
        $this->io = $io;
        $this->configurationService = $configurationService;
    }

    /**
     * @throws Exception
     */
    public function install(Instance $target, Setup $setup): Installation
    {
        $newInstallation = $this->createInstallation($target, $setup);

        $this->io->text('uploading build to '.$newInstallation->getFolder());
        $this->uploadBuild($target->getConnection(), $setup, $newInstallation);

        $this->io->text('creating and linking shared folders');
        $this->createAndLinkSharedFolders($target->getConnection(), $target, $newInstallation);

        return $newInstallation;
    }

    private function createInstallation(Instance $target, Setup $setup): Installation
    {
        $identification = $setup->getIdentification();
        $installationFolder = $target->getInstallationsFolder().DIRECTORY_SEPARATOR.$identification;
        if ($target->getConnection()->checkFolderExists($installationFolder)) {
            $duplicationCounter = 1;
            while ($target->getConnection()->checkFolderExists($installationFolder.'-'.$duplicationCounter)) {
                ++$duplicationCounter;
            }
            $installationFolder .= '-'.$duplicationCounter;
        }

        $maxNumber = 0;
        foreach ($target->getInstallations() as $installation) {
            $maxNumber = max((int) $installation->getNumber(), $maxNumber);
        }
        ++$maxNumber;

        $installation = new Installation($installationFolder, $maxNumber, $setup);
        $target->addInstallation($installation);

        return $installation;
    }

    /**
     * @throws \Exception
     */
    private function uploadBuild(Connection $connection, Setup $setup, Installation $installation): void
    {
        // make empty dir for new release
        $connection->createOrClearFolder($installation->getFolder());

        // transfer release packet
        $assetPath = $installation->getFolder().DIRECTORY_SEPARATOR.$setup->getIdentification().'.tar.gz';
        $connection->writeFile($assetPath, $setup->getContent());

        // unpack release packet
        $connection->uncompressTarGz($assetPath, $installation->getFolder());

        // remove release packet
        $connection->removeFile($assetPath);
    }

    /**
     * @throws \Exception
     */
    private function createAndLinkSharedFolders(Connection $connection, Instance $target, Installation $installation): void
    {
        $instanceSharedFolder = $target->getSharedFolder();
        $installationSharedFolders = $this->configurationService->getSharedFolders();
        foreach ($installationSharedFolders as $sharedFolder) {
            $sharedFolderTarget = $instanceSharedFolder.DIRECTORY_SEPARATOR.$sharedFolder;
            $releaseFolderSource = $installation->getFolder().DIRECTORY_SEPARATOR.$sharedFolder;

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

    public function isTakenOffline(Instance $instance, Installation $installation): void
    {
        $installation->stopOnlinePeriod();
        $this->saveInstallation($instance->getConnection(), $installation);
    }

    public function wasTakenOnline(Instance $instance, Installation $installation): void
    {
        $installation->startOnlinePeriod();
        $this->saveInstallation($instance->getConnection(), $installation);
    }

    private function saveInstallation(Connection $connection, Installation $installation): void
    {
        $metaJson = json_encode($installation->toArray(), JSON_PRETTY_PRINT);
        $agnesFilePath = $installation->getFolder().DIRECTORY_SEPARATOR.self::AGNES_FILE_NAME;
        $connection->writeFile($agnesFilePath, $metaJson);
    }

    /**
     * @return Installation[]
     *
     * @throws Exception
     */
    public function loadInstallations(Instance $instance): array
    {
        $installationsFolder = $instance->getInstallationsFolder();

        $folders = $instance->getConnection()->getFolders($installationsFolder);

        $installations = [];

        foreach ($folders as $folder) {
            $installation = $this->getInstallationFromFolder($instance, $folder);
            if (null !== $installation) {
                $installations[] = $installation;
            }
        }

        return $installations;
    }

    /**
     * @throws Exception
     */
    private function getInstallationFromFolder(Instance $instance, string $folder): ?Installation
    {
        $installationPath = $instance->getInstallationsFolder().DIRECTORY_SEPARATOR.$folder;
        $agnesFilePath = $installationPath.DIRECTORY_SEPARATOR.self::AGNES_FILE_NAME;

        if (!$instance->getConnection()->checkFileExists($agnesFilePath)) {
            return null;
        }

        $metaJson = $instance->getConnection()->readFile($agnesFilePath);
        $array = json_decode($metaJson, true);

        return Installation::fromArray($installationPath, $array);
    }
}
