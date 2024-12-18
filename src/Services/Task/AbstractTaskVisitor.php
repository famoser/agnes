<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    /**
     * @throws \Exception
     */
    public function visitCopy(Copy $copy)
    {
        return $this->visitDefault($copy);
    }

    /**
     * @throws \Exception
     */
    public function visitDeploy(Deploy $deploy)
    {
        return $this->visitDefault($deploy);
    }

    /**
     * @throws \Exception
     */
    public function visitRelease(Release $release)
    {
        return $this->visitDefault($release);
    }

    /**
     * @throws \Exception
     */
    public function visitRollback(Rollback $rollback)
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
    public function visitRun(Run $run)
    {
        return $this->visitDefault($run);
    }

    /**
     * @throws \Exception
     */
    public function visitClear(Clear $clear)
    {
        return $this->visitDefault($clear);
    }

    /**
     * @throws \Exception
     */
    protected function visitDefault(AbstractTask $payload)
    {
        throw new \Exception('Not implemented for '.$payload->describe());
    }
}
