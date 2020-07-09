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
    private $releaseOrCommitish;

    /**
     * @var OnlinePeriod[]
     */
    private $onlinePeriods;

    /**
     * Installation constructor.
     */
    public function __construct(string $folder, int $number, string $releaseOrCommitish, array $onlinePeriods = [])
    {
        $this->folder = $folder;
        $this->number = $number;
        $this->releaseOrCommitish = $releaseOrCommitish;
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

    public function getReleaseOrCommitish(): string
    {
        return $this->releaseOrCommitish;
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
    public function startOnlinePeriod()
    {
        $onlinePeriod = new OnlinePeriod(new DateTime(), null);
        $this->onlinePeriods[] = $onlinePeriod;
    }

    /**
     * persists that the installation is now taken offline.
     */
    public function stopOnlinePeriod()
    {
        if (0 === count($this->onlinePeriods)) {
            return;
        }

        $lastPeriod = $this->onlinePeriods[count($this->onlinePeriods) - 1];
        $lastPeriod->setEnd(new DateTime());
    }

    public function toArray(): array
    {
        $array = ['number' => $this->number, 'release_or_commitish' => $this->releaseOrCommitish, 'online_periods' => []];

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

        return new Installation($folder, $array['number'], $array['release_or_commitish'], $onlinePeriods);
    }
}
