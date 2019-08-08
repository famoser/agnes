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
     * CopySharedService constructor.
     * @param PolicyService $policyService
     */
    public function __construct(PolicyService $policyService)
    {
        $this->policyService = $policyService;
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

        if ($copyShared->getSource()->getServerName() === $copyShared->getTarget()->getServerName()) {
            // or either source or target are local
        }
    }
}