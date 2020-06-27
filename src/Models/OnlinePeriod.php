<?php

namespace Agnes\Models;

use DateTime;

class OnlinePeriod
{
    /**
     * @var DateTime
     */
    private $start;

    /**
     * @var DateTime|null
     */
    private $end;

    /**
     * OnlinePeriod constructor.
     */
    public function __construct(DateTime $start, ?DateTime $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): DateTime
    {
        return $this->start;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setEnd(?DateTime $end): void
    {
        $this->end = $end;
    }
}
