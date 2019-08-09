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
     * @param DateTime $start
     * @param DateTime|null $end
     */
    public function __construct(DateTime $start, ?DateTime $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return DateTime
     */
    public function getStart(): DateTime
    {
        return $this->start;
    }

    /**
     * @return DateTime|null
     */
    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    /**
     * @param DateTime|null $end
     */
    public function setEnd(?DateTime $end): void
    {
        $this->end = $end;
    }
}