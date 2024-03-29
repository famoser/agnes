<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Symfony\Component\Console\Style\StyleInterface;

class FileService
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * FileService constructor.
     */
    public function __construct(StyleInterface $io, ConfigurationService $configurationService)
    {
        $this->io = $io;
        $this->configurationService = $configurationService;
    }

    /**
     * @throws \Exception
     */
    public function allRequiredFilesExist(Instance $instance): bool
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        $missingFiles = [];
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder.DIRECTORY_SEPARATOR.$configuredFileKey;

            if ($configuredFile->getIsRequired() && !file_exists($expectedFilePath)) {
                $missingFiles[$configuredFileKey] = $expectedFilePath;
            }
        }

        if (count($missingFiles) > 0) {
            $this->io->error('For instance '.$instance->describe().' the required file(s) '.implode(', ', array_keys($missingFiles)).' are missing, expected at '.implode(', ', $missingFiles));

            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function uploadFiles(Instance $instance, Installation $installation): void
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder.DIRECTORY_SEPARATOR.$configuredFileKey;

            if (file_exists($expectedFilePath)) {
                $fullPath = $installation->getFolder().DIRECTORY_SEPARATOR.$configuredFileKey;
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

        return $configFolder.DIRECTORY_SEPARATOR.
            $instance->getServerName().DIRECTORY_SEPARATOR.
            $instance->getEnvironmentName().DIRECTORY_SEPARATOR.
            $instance->getStage();
    }
}
