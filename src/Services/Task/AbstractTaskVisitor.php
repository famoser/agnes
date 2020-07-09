<?php

namespace Agnes\Services\Task;

use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\Build;
use Agnes\Models\Task\CopyShared;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Download;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;

abstract class AbstractTaskVisitor
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
    public function visitDownload(Download $downloadGithub)
    {
        return $this->visitDefault($downloadGithub);
    }

    /**
     * @throws \Exception
     */
    public function visitBuild(Build $build)
    {
        return $this->visitDefault($build);
    }

    /**
     * @throws \Exception
     */
    protected function visitDefault(AbstractTask $payload): bool
    {
        throw new \Exception('Not implemented for '.$payload->describe());
    }
}
