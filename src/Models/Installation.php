<?php

namespace Agnes\Models;

use Agnes\Models\Installation\OnlinePeriod;

class Installation
{
    /**
     * @param OnlinePeriod[]  $onlinePeriods
     */
    public function __construct(
        private string $folder,
        private int $number,
        private string $commitish,
        private string $releaseOrHash,
        private array $onlinePeriods = []
    ) {
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
        $onlinePeriod = new OnlinePeriod(new \DateTime(), null);
        $this->onlinePeriods[] = $onlinePeriod;
    }

    /**
     * persists that the installation is now taken offline.
     */
    public function stopOnlinePeriod(): void
    {
        if ([] === $this->onlinePeriods) {
            return;
        }

        $lastPeriod = $this->onlinePeriods[count($this->onlinePeriods) - 1];
        $lastPeriod->setEnd(new \DateTime());
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
