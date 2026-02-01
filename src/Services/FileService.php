<?php

namespace Agnes\Services;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Symfony\Component\Console\Style\StyleInterface;

class FileService
{
    /**
     * FileService constructor.
     */
    public function __construct(private StyleInterface $io, private ConfigurationService $configurationService)
    {
    }

    /**
     *
     */
    public function allRequiredFilesExist(Instance $instance): bool
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        $missingFiles = [];
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder . DIRECTORY_SEPARATOR . $configuredFileKey;

            if ($configuredFile->getIsRequired() && !file_exists($expectedFilePath)) {
                $missingFiles[$configuredFileKey] = $expectedFilePath;
            }
        }

        if ($missingFiles !== []) {
            $this->io->error('For instance ' . $instance->describe() . ' the required file(s) ' . implode(', ', array_keys($missingFiles)) . ' are missing, expected at ' . implode(', ', $missingFiles));

            return false;
        }

        return true;
    }

    /**
     *
     */
    public function uploadFiles(Instance $instance, Installation $installation): void
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder . DIRECTORY_SEPARATOR . $configuredFileKey;

            if (file_exists($expectedFilePath)) {
                $fullPath = $installation->getFolder() . DIRECTORY_SEPARATOR . $configuredFileKey;
                $folder = dirname($fullPath);
                $content = file_get_contents($expectedFilePath);
                $instance->getConnection()->createFolder($folder);
                $instance->getConnection()->writeFile($fullPath, $content);
            }
        }
    }

    private function getLocalConfigFolderPath(Instance $instance): string
    {
        $configFolder = $this->configurationService->getConfigFolder();

        return $configFolder . DIRECTORY_SEPARATOR .
            $instance->getServerName() . DIRECTORY_SEPARATOR .
            $instance->getEnvironmentName() . DIRECTORY_SEPARATOR .
            $instance->getStage();
    }
}
