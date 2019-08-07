<?php


namespace Agnes\Deploy;


use Agnes\Release\Release;
use Agnes\Services\Configuration\Installation;

class Deployment
{
    /**
     * @var Installation
     */
    private $target;

    /**
     * @var Release
     */
    private $release;

    /**
     * Deployment constructor.
     * @param Installation $target
     * @param Release $release
     */
    public function __construct(Installation $target, Release $release)
    {
        $this->target = $target;
        $this->release = $release;
    }

    /**
     * @return Installation
     */
    public function getTarget(): Installation
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