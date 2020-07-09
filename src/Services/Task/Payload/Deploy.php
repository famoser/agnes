<?php

namespace Agnes\Actions;

use Agnes\Actions\Visitors\AbstractActionVisitor;
use Agnes\Models\Instance;
use Agnes\Models\Setup;

class Deploy extends AbstractPayload
{
    /**
     * @var Instance
     */
    private $target;

    /**
     * @var Setup
     */
    private $setup;

    /**
     * Deployment constructor.
     *
     * @param string[] $files
     */
    public function __construct(Setup $setup, Instance $target)
    {
        $this->setup = $setup;
        $this->target = $target;
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function getSetup(): Setup
    {
        return $this->setup;
    }

    public function describe(): string
    {
        return 'deploy '.$this->getSetup()->getIdentification().' to '.$this->getTarget()->describe();
    }

    public function accept(AbstractActionVisitor $abstractActionVisitor): bool
    {
        return $abstractActionVisitor->visitDeploy($this);
    }
}
