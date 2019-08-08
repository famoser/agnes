<?php


namespace Agnes\Deploy;


use Agnes\Models\Tasks\Instance;
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
     * @var string[]
     */
    private $files;

    /**
     * Deployment constructor.
     * @param ReleaseWithAsset $release
     * @param Instance $target
     * @param array $files
     */
    public function __construct(ReleaseWithAsset $release, Instance $target, array $files)
    {
        $this->target = $target;
        $this->release = $release;
        $this->files = $files;
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

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}