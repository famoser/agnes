<?php


namespace Agnes\Actions;

use Agnes\Services\ConfigurationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Exception;

class CopySharedAction extends AbstractAction
{
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
        parent::__construct($policyService);

        $this->configurationService = $configurationService;
        $this->instanceService = $instanceService;
    }

    /**
     * check the instance of the payload is of the expected type to execute in execute()
     *
     * @param CopyShared $copyShared
     * @return bool
     */
    protected function canProcessPayload($copyShared): bool
    {
        if (!$copyShared instanceof CopyShared) {
            return false;
        }

        // technical limitation: only copy from same connection
        if (!$copyShared->getSource()->getConnection()->equals($copyShared->getTarget()->getConnection())) {
            return false;
        }

        return true;
    }

    /**
     * @param CopyShared $copyShared
     * @throws Exception
     */
    protected function doExecute($copyShared)
    {
        $sourceSharedPath = $this->instanceService->getSharedPath($copyShared->getSource());
        $targetSharedPath = $this->instanceService->getSharedPath($copyShared->getTarget());
        $connection = $copyShared->getSource()->getConnection();

        $sharedFolders = $this->configurationService->getSharedFolders();
        foreach ($sharedFolders as $sharedFolder) {
            $sourceFolderPath = $sourceSharedPath . DIRECTORY_SEPARATOR . $sharedFolder;
            $targetFolderPath = $targetSharedPath . DIRECTORY_SEPARATOR . $sharedFolder;

            $connection->copyFolderContent($sourceFolderPath, $targetFolderPath);
        }
    }
}