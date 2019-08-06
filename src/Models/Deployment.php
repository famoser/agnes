<?php


namespace Agnes\Deploy;


use Agnes\Services\Configuration\Installation;

class Deployment
{
    /**
     * @var Installation
     */
    private $target;

    /**
     * @var string
     */
    private $releaseTag;

    /**
     * @return Installation
     */
    public function getTarget(): Installation
    {
        return $this->target;
    }

    /**
     * @param Installation $target
     */
    public function setTarget(Installation $target): void
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getReleaseTag(): string
    {
        return $this->releaseTag;
    }

    /**
     * @param string $releaseTag
     */
    public function setReleaseTag(string $releaseTag): void
    {
        $this->releaseTag = $releaseTag;
    }
}