<?php

namespace Agnes\Models;

use DateTime;
use Exception;

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
     * @var Setup
     */
    private $setup;

    /**
     * @var OnlinePeriod[]
     */
    private $onlinePeriods;

    /**
     * Installation constructor.
     */
    public function __construct(string $folder, int $number, Setup $setup, array $onlinePeriods = [])
    {
        $this->folder = $folder;
        $this->number = $number;
        $this->setup = $setup;
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

    public function getSetup(): Setup
    {
        return $this->setup;
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
     *
     * @throws Exception
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
        $array = ['number' => $this->number, 'setup' => $this->setup->toArray(), 'online_periods' => []];

        foreach ($this->onlinePeriods as $onlinePeriod) {
            $array['online_periods'][] = $onlinePeriod->toArray();
        }

        return $array;
    }

    public static function fromArray(string $folder, array $array): Installation
    {
        $setup = Setup::fromArray($array['setup']);
        $onlinePeriods = [];
        foreach ($array['online_periods'] as $onlinePeriod) {
            $onlinePeriods[] = OnlinePeriod::fromArray($onlinePeriod);
        }

        return new Installation($folder, $array['number'], $setup, $onlinePeriods);
    }

    public function getAgnesMetaFilePath(): string
    {
        return $this->get.DIRECTORY_SEPARATOR.'.agnes';
    }
}
