<?php

namespace Agnes\Models;

use Agnes\Actions\Release;
use DateTime;
use Exception;

class Installation
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var int|null
     */
    private $number;

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
     *
     * @param int $number
     */
    public function __construct(string $path, ?int $number = null, ?Release $release = null, array $onlinePeriods = [])
    {
        $this->path = $path;
        $this->number = $number;
        $this->release = $release;
        $this->onlinePeriods = $onlinePeriods;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    /**
     * @return Release|null ?Release
     */
    public function getRelease(): ?Release
    {
        return $this->release;
    }

    /**
     * if this installation was online at some time.
     *
     * @return bool
     */
    public function hasOnlinePeriods()
    {
        return count($this->onlinePeriods) > 0;
    }

    /**
     * if this installation is online right now.
     *
     * @return bool
     */
    public function isOnline()
    {
        $lastPeriod = $this->getLastOnlinePeriod();

        return null !== $lastPeriod && null === $lastPeriod->getEnd();
    }

    /**
     * @return OnlinePeriod[]
     */
    public function getOnlinePeriods(): array
    {
        return $this->onlinePeriods;
    }

    /**
     * persists that the installation is now taken online.
     */
    public function takeOnline()
    {
        $onlinePeriod = new OnlinePeriod(new DateTime(), null);
        $this->onlinePeriods[] = $onlinePeriod;
    }

    /**
     * persists that the installation is now taken offline.
     *
     * @throws Exception
     */
    public function takeOffline()
    {
        $lastPeriod = $this->getLastOnlinePeriod();
        if (null !== $lastPeriod) {
            $lastPeriod->setEnd(new DateTime());
        }
    }

    public function setRelease(int $number, Release $release)
    {
        $this->number = $number;
        $this->release = $release;
    }

    /**
     * @return DateTime|null
     */
    public function getLastOnline()
    {
        $lastPeriod = $this->getLastOnlinePeriod();

        if (null === $lastPeriod) {
            return null;
        }

        return null !== $lastPeriod->getEnd() ? $lastPeriod->getEnd() : $lastPeriod->getStart();
    }

    /**
     * @return OnlinePeriod|null
     */
    private function getLastOnlinePeriod()
    {
        if (0 === count($this->onlinePeriods)) {
            return null;
        }

        return $this->onlinePeriods[count($this->onlinePeriods) - 1];
    }

    /**
     * @return bool
     */
    public function isSameReleaseName(string $releaseName)
    {
        return null != $this->getRelease() && $this->getRelease()->getName() === $releaseName;
    }
}
