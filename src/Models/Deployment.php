<?php


namespace Agnes\Deploy;


use Agnes\Models\Tasks\Instance;
use Agnes\Release\Release;

class Deployment
{
    /**
     * @var Instance
     */
    private $target;

    /**
     * @var Release
     */
    private $release;

    /**
     * Deployment constructor.
     * @param Instance $target
     * @param Release $release
     */
    public function __construct(Instance $target, Release $release)
    {
        $this->target = $target;
        $this->release = $release;
    }

    /**
     * @return Instance
     */
    public function getTarget(): Instance
    {
        return $this->target;
    }

    /**
     * @return Release
     */
    public function getRelease(): Release
    {
        return $this->release;
    }
}