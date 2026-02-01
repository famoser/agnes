<?php

namespace Agnes\Services\Task;

use Agnes\Models\Task\AbstractTask;
use Agnes\Models\Task\Build;
use Agnes\Models\Task\Clear;
use Agnes\Models\Task\Copy;
use Agnes\Models\Task\Deploy;
use Agnes\Models\Task\Download;
use Agnes\Models\Task\Release;
use Agnes\Models\Task\Rollback;
use Agnes\Models\Task\Run;

abstract class AbstractTaskVisitor
{
    public function visitCopy(Copy $copy)
    {
        return $this->visitDefault($copy);
    }

    public function visitDeploy(Deploy $deploy)
    {
        return $this->visitDefault($deploy);
    }

    public function visitRelease(Release $release)
    {
        return $this->visitDefault($release);
    }

    public function visitRollback(Rollback $rollback)
    {
        return $this->visitDefault($rollback);
    }

    public function visitDownload(Download $downloadGithub)
    {
        return $this->visitDefault($downloadGithub);
    }

    public function visitBuild(Build $build)
    {
        return $this->visitDefault($build);
    }

    public function visitRun(Run $run)
    {
        return $this->visitDefault($run);
    }

    public function visitClear(Clear $clear)
    {
        return $this->visitDefault($clear);
    }

    protected function visitDefault(AbstractTask $payload)
    {
        throw new \Exception('Not implemented for ' . $payload->describe());
    }
}
