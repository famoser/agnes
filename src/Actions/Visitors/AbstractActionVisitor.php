<?php

namespace Agnes\Actions\Visitors;

use Agnes\Actions\AbstractPayload;
use Agnes\Actions\CopyShared;
use Agnes\Actions\Deploy;
use Agnes\Actions\Release;
use Agnes\Actions\Rollback;
use Agnes\Actions\Setup;

abstract class AbstractActionVisitor
{
    /**
     * @throws \Exception
     */
    public function visitCopyShared(CopyShared $copyShared): bool
    {
        return $this->visitDefault($copyShared);
    }

    /**
     * @throws \Exception
     */
    public function visitDeploy(Deploy $deploy): bool
    {
        return $this->visitDefault($deploy);
    }

    /**
     * @throws \Exception
     */
    public function visitRelease(Release $release): bool
    {
        return $this->visitDefault($release);
    }

    /**
     * @throws \Exception
     */
    public function visitRollback(Rollback $rollback): bool
    {
        return $this->visitDefault($rollback);
    }

    /**
     * @throws \Exception
     */
    public function visitSetup(Setup $setup): bool
    {
        return $this->visitDefault($setup);
    }

    /**
     * @throws \Exception
     */
    public function visitDefault(AbstractPayload $payload): bool
    {
        throw new \Exception('Not implemented for '.$payload->describe());
    }
}
