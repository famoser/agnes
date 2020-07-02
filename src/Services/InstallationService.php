<?php

namespace Agnes\Services;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Models\Setup;
use Exception;

class InstallationService
{
    const AGNES_FILE_NAME = '.agnes';

    public function createInstallation(Instance $target, Setup $setup): Installation
    {
        $identification = $setup->getIdentification();
        $installationFolder = $target->getInstallationsFolder().DIRECTORY_SEPARATOR.$identification;
        if ($target->getConnection()->checkFolderExists($installationFolder)) {
            $duplicationCounter = 1;
            while ($target->getConnection()->checkFolderExists($installationFolder).'-'.$duplicationCounter) {
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
            $installation = $this->getInstallationFromPath($instance->getConnection(), $folder);
            if (null !== $installation) {
                $installations[] = $installation;
            }
        }

        return $installations;
    }

    /**
     * @throws Exception
     */
    private function getInstallationFromPath(Connection $connection, string $installationPath): ?Installation
    {
        $agnesFilePath = $installationPath.DIRECTORY_SEPARATOR.self::AGNES_FILE_NAME;

        if (!$connection->checkFileExists($agnesFilePath)) {
            return null;
        }

        $metaJson = $connection->readFile($agnesFilePath);
        $array = json_decode($metaJson, true);

        return Installation::fromArray($installationPath, $array);
    }
}