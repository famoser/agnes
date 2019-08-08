<?php


namespace Agnes\Services;

use Agnes\Services\CopyShared\CopyShared;
use Agnes\Services\Rollback\Rollback;
use Exception;

class CopySharedService
{
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
     */
    private function copyShared(CopyShared $copyShared)
    {

    }
}