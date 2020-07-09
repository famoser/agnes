<?php

namespace Agnes\Actions;

use Agnes\Actions\Visitors\AbstractActionVisitor;
use Agnes\Models\Installation;
use Agnes\Models\Instance;

class Rollback extends AbstractPayload
{
    /**
     * @var Instance
     */
    private $instance;

    /**
     * @var Installation
     */
    private $target;

    /**
     * Rollback constructor.
     */
    public function __construct(Instance $instance, Installation $target)
    {
        $this->instance = $instance;
        $this->target = $target;
    }

    public function getInstance(): Instance
    {
        return $this->instance;
    }

    public function getTarget(): Installation
    {
        return $this->target;
    }

    public function describe(): string
    {
        return 'rollback '.$this->getInstance()->describe().' at '.$this->getInstance()->getCurrentInstallation()->getReleaseOrCommitish().'to '.$this->getTarget()->getReleaseOrCommitish();
    }

    public function accept(AbstractActionVisitor $abstractActionVisitor): bool
    {
        return $abstractActionVisitor->visitRollback($this);
    }
}
