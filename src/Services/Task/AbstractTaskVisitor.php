<?php

namespace Agnes\Services\Task;

use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\Build;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Download;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;
use Agnes\Models\Task\Run;
use Exception;

abstract class AbstractTaskVisitor
{
    /**
     * @throws Exception
     */
    public function visitCopyShared(Copy $copyShared)
    {
        return $this->visitDefault($copyShared);
    }

    /**
     * @throws Exception
     */
    public function visitDeploy(Deploy $deploy)
    {
        return $this->visitDefault($deploy);
    }

    /**
     * @throws Exception
     */
    public function visitRelease(Release $release)
    {
        return $this->visitDefault($release);
    }

    /**
     * @throws Exception
     */
    public function visitRollback(Rollback $rollback)
    {
        return $this->visitDefault($rollback);
    }

    /**
     * @throws Exception
     */
    public function visitDownload(Download $downloadGithub)
    {
        return $this->visitDefault($downloadGithub);
    }

    /**
     * @throws Exception
     */
    public function visitBuild(Build $build)
    {
        return $this->visitDefault($build);
    }

    /**
     * @throws Exception
     */
    public function visitRun(Run $run)
    {
        return $this->visitDefault($run);
    }

    /**
     * @throws Exception
     *
     * @return mixed
     */
    protected function visitDefault(AbstractTask $payload)
    {
        throw new Exception('Not implemented for '.$payload->describe());
    }
}
