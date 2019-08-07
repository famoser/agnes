<?php


namespace Agnes\Deploy;


use Agnes\Models\Tasks\Instance;
use Agnes\Release\Release;
use Agnes\Services\Github\ReleaseWithAsset;

class Deploy
{
    /**
     * @var Instance
     */
    private $target;

    /**
     * @var ReleaseWithAsset
     */
    private $release;

    /**
     * Deployment constructor.
     * @param Release $release
     * @param Instance $target
     */
    public function __construct(ReleaseWithAsset $release, Instance $target)
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
     * @return ReleaseWithAsset
     */
    public function getRelease(): ReleaseWithAsset
    {
        return $this->release;
    }
}