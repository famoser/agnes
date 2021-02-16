<?php

namespace Agnes\Models;

use Agnes\Models\Installation\OnlinePeriod;
use DateTime;

class Installation
{
    /**
     * @var string
     */
    private $folder;

    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $commitish;

    /**
     * @var string
     */
    private $releaseOrHash;

    /**
     * @var OnlinePeriod[]
     */
    private $onlinePeriods;

    /**
     * Installation constructor.
     */
    public function __construct(string $folder, int $number, string $commitish, string $releaseOrHash, array $onlinePeriods = [])
    {
        $this->folder = $folder;
        $this->number = $number;
        $this->commitish = $commitish;
        $this->releaseOrHash = $releaseOrHash;
        $this->onlinePeriods = $onlinePeriods;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getReleaseOrHash(): string
    {
        return $this->releaseOrHash;
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
    public function startOnlinePeriod(): void
    {
        $onlinePeriod = new OnlinePeriod(new DateTime(), null);
        $this->onlinePeriods[] = $onlinePeriod;
    }

    /**
     * persists that the installation is now taken offline.
     */
    public function stopOnlinePeriod(): void
    {
        if (0 === count($this->onlinePeriods)) {
            return;
        }

        $lastPeriod = $this->onlinePeriods[count($this->onlinePeriods) - 1];
        $lastPeriod->setEnd(new DateTime());
    }

    public function toArray(): array
    {
        $array = ['number' => $this->number, 'commitish' => $this->commitish, 'release_or_hash' => $this->releaseOrHash, 'online_periods' => []];

        foreach ($this->onlinePeriods as $onlinePeriod) {
            $array['online_periods'][] = $onlinePeriod->toArray();
        }

        return $array;
    }

    public static function fromArray(string $folder, array $array): Installation
    {
        $onlinePeriods = [];
        foreach ($array['online_periods'] as $onlinePeriod) {
            $onlinePeriods[] = OnlinePeriod::fromArray($onlinePeriod);
        }

        return new Installation($folder, $array['number'], $array['commitish'], $array['release_or_hash'], $onlinePeriods);
    }
}
