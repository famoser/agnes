<?php

namespace Agnes\Actions;

use Agnes\Actions\Visitors\AbstractActionVisitor;
use Agnes\Models\Instance;

class CopyShared extends AbstractPayload
{
    /**
     * @var Instance
     */
    private $source;

    /**
     * @var Instance
     */
    private $target;

    /**
     * CopyShared constructor.
     */
    public function __construct(Instance $source, Instance $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    public function getSource(): Instance
    {
        return $this->source;
    }

    public function getTarget(): Instance
    {
        return $this->target;
    }

    public function describe(): string
    {
        return 'copy shared data from '.$this->getSource()->describe().' to '.$this->getTarget()->describe();
    }

    public function accept(AbstractActionVisitor $abstractActionVisitor): bool
    {
        return $abstractActionVisitor->visitCopyShared($this);
    }
}
