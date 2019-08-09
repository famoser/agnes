<?php


namespace Agnes\Services\CopyShared;


use Agnes\Models\Instance;

class CopyShared
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
     * @param Instance $source
     * @param Instance $target
     */
    public function __construct(Instance $source, Instance $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    /**
     * @return Instance
     */
    public function getSource(): Instance
    {
        return $this->source;
    }

    /**
     * @return Instance
     */
    public function getTarget(): Instance
    {
        return $this->target;
    }
}