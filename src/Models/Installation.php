<?php


namespace Agnes\Models;

use Agnes\Models\Tasks\OnlinePeriod;
use Agnes\Release\Release;

class Installation
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var Release?
     */
    private $release;

    /**
     * @var OnlinePeriod[]
     */
    private $onlinePeriods;

    /**
     * Installation constructor.
     * @param string $path
     * @param Release|null $release
     * @param array $onlinePeriods
     */
    public function __construct(string $path, ?Release $release = null, array $onlinePeriods = [])
    {
        $this->path = $path;
        $this->release = $release;
        $this->onlinePeriods = $onlinePeriods;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return Release|null ?Release
     */
    public function getRelease(): ?Release
    {
        return $this->release;
    }

    /**
     * if this installation was online at some time
     *
     * @return bool
     */
    public function hasOnlinePeriods()
    {
        return count($this->onlinePeriods) > 0;
    }

    /**
     * @return OnlinePeriod[]
     */
    public function getOnlinePeriods(): array
    {
        return $this->onlinePeriods;
    }

    /**
     * persists that the installation is now taken online
     */
    public function takeOnline()
    {
        $onlinePeriod = new OnlinePeriod(new \DateTime(), null);
        $this->onlinePeriods[] = $onlinePeriod;
    }

    /**
     * persists that the installation is now taken offline
     * @throws \Exception
     */
    public function takeOffline()
    {
        $lastPeriod = $this->onlinePeriods[count($this->onlinePeriods) - 1];
        $lastPeriod->setEnd(new \DateTime());
    }

    /**
     * @param Release $release
     */
    public function setRelease(Release $release)
    {
        $this->release = $release;
    }
}