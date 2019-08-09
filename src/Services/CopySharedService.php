<?php


namespace Agnes\Services;

use Agnes\Services\CopyShared\CopyShared;
use Agnes\Services\Rollback\Rollback;
use Exception;

class CopySharedService
{
    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * CopySharedService constructor.
     * @param PolicyService $policyService
     * @param ConfigurationService $configurationService
     */
    public function __construct(PolicyService $policyService, ConfigurationService $configurationService)
    {
        $this->policyService = $policyService;
        $this->configurationService = $configurationService;
    }

    /**
     * @param CopyShared[] $copyShareds
     * @throws Exception
     */
    public function copySharedMultiple(array $copyShareds)
    {
        foreach ($copyShareds as $rollback) {
            $this->copyShared($rollback);
        }
    }

    /**
     * @param CopyShared $copyShared
     * @throws Exception
     */
    private function copyShared(CopyShared $copyShared)
    {
        if (!$this->policyService->canCopyShared($copyShared)) {
            return;
        }

        $sharedFolders = $this->configurationService->getSharedFolders();

        if (!$copyShared->getSource()->getConnection()->equals($copyShared->getTarget()->getConnection())) {
            return;
        }

        $sourcePath = $copyShared->getSource()->getCurrentInstallation()->getPath();
        $targetPath = $copyShared->getTarget()->getCurrentInstallation()->getPath();
        $connection = $copyShared->getSource()->getConnection();

        $commands = [];
        foreach ($sharedFolders as $sharedFolder) {
            $sourceFolderPath = $sourcePath . DIRECTORY_SEPARATOR . $sharedFolder;
            $targetFolderPath = $targetPath . DIRECTORY_SEPARATOR . $sharedFolder;
            $commands = array_merge(
                $commands,
                [
                    "rm -rf $targetFolderPath",
                    "cp -r $sourceFolderPath $targetFolderPath"
                ]
            );
        }

        $connection->execute(...$commands);
    }
}