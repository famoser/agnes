<?php


namespace Agnes\Services;

use Agnes\Services\CopyShared\CopyShared;
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
     * @var InstanceService
     */
    private $instanceService;

    /**
     * CopySharedService constructor.
     * @param PolicyService $policyService
     * @param ConfigurationService $configurationService
     * @param InstanceService $instanceService
     */
    public function __construct(PolicyService $policyService, ConfigurationService $configurationService, InstanceService $instanceService)
    {
        $this->policyService = $policyService;
        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
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

        $sourceSharedPath = $this->instanceService->getSharedPath($copyShared->getSource());
        $targetSharedPath = $this->instanceService->getSharedPath($copyShared->getTarget());
        $connection = $copyShared->getSource()->getConnection();

        foreach ($sharedFolders as $sharedFolder) {
            $sourceFolderPath = $sourceSharedPath . DIRECTORY_SEPARATOR . $sharedFolder;
            $targetFolderPath = $targetSharedPath . DIRECTORY_SEPARATOR . $sharedFolder;

            $connection->copyFolderContent($sourceFolderPath, $targetFolderPath);
        }
    }
}