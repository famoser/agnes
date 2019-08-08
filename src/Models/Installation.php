<?php


namespace Agnes\Models;

use Agnes\Models\Tasks\OnlinePeriod;
use Agnes\Release\Release;
use DateTime;
use Exception;

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
     * if this installation is online right now
     *
     * @return bool
     */
    public function isOnline()
    {
        $lastPeriod = $this->getLastOnlinePeriod();

        return $lastPeriod !== null && $lastPeriod->getEnd() === null;
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
        $onlinePeriod = new OnlinePeriod(new DateTime(), null);
        $this->onlinePeriods[] = $onlinePeriod;
    }

    /**
     * persists that the installation is now taken offline
     * @throws Exception
     */
    public function takeOffline()
    {
        $lastPeriod = $this->getLastOnlinePeriod();
        if ($lastPeriod !== null) {
            $lastPeriod->setEnd(new DateTime());
        }
    }

    /**
     * @param Release $release
     */
    public function setRelease(Release $release)
    {
        $this->release = $release;
    }

    /**
     * @return DateTime|null
     */
    public function getLastOnline()
    {
        $lastPeriod = $this->getLastOnlinePeriod();

        if ($lastPeriod === null) {
            return null;
        }

        return $lastPeriod->getEnd() !== null ? $lastPeriod->getEnd() : $lastPeriod->getStart();
    }

    /**
     * @return OnlinePeriod|null
     */
    private function getLastOnlinePeriod()
    {
        if (count($this->onlinePeriods) === 0) {
            return null;
        }

        return $this->onlinePeriods[count($this->onlinePeriods) - 1];
    }
}