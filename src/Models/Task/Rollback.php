<?php

namespace Agnes\Models\Task;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Agnes\Services\Task\AbstractTaskVisitor;

class Rollback extends AbstractTask
{
    const NAME = 'rollback';

    /**
     * @var Instance
     */
    private $target;

    /**
     * @var Installation
     */
    private $installation;

    /**
     * Rollback constructor.
     */
    public function __construct(Instance $target, Installation $installation)
    {
        $this->target = $target;
        $this->installation = $installation;
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function getInstallation(): Installation
    {
        return $this->installation;
    }

    public function describe(): string
    {
        return 'rollback '.$this->getTarget()->describe().' at '.$this->getTarget()->getCurrentInstallation()->getReleaseOrCommitish().'to '.$this->getInstallation()->getReleaseOrCommitish();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitRollback($this);
    }

    public function name(): string
    {
        return self::NAME;
    }
}
