<?php

namespace Agnes\Models\Installation;

class OnlinePeriod
{
    /**
     * OnlinePeriod constructor.
     */
    public function __construct(private \DateTime $start, private ?\DateTime $end)
    {
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function setEnd(?\DateTime $end): void
    {
        $this->end = $end;
    }

    public function toArray(): array
    {
        $array = ['start' => $this->start->format('c')];

        if (null !== $this->end) {
            $array['end'] = $this->end->format('c');
        }

        return $array;
    }

    /**
     *
     */
    public static function fromArray(array $array): self
    {
        $start = new \DateTime($array['start']);
        $end = isset($array['end']) ? new \DateTime($array['end']) : null;

        return new self($start, $end);
    }
}
