<?php


namespace Agnes\Models\Policies;


use Agnes\Models\Filter;

abstract class LayeredPolicy extends Policy
{
    /**
     * @var string[][]
     */
    private $layers = [];

    /**
     * LayeredPolicy constructor.
     * @param Filter|null $filter
     * @param string[][] $layers
     */
    public function __construct(?Filter $filter, array $layers)
    {
        parent::__construct($filter);

        foreach ($layers as $key => $entries) {
            $this->layers[(int)$key] = $entries;
        }

        ksort($this->layers);
    }

    /**
     * @param string $value
     * @return int|string
     */
    public function getLayerIndex(string $value)
    {
        foreach ($this->layers as $index => $entries) {
            foreach ($entries as $entry) {
                if ($entry === $value) {
                    return $index;
                }
            }
        }

        return false;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function isLowestLayer(int $index)
    {
        $availableLayers = array_keys($this->layers);

        return min($availableLayers) === $index;
    }

    /**
     * @param string $index
     * @return bool
     */
    public function isHighestLayer(string $index)
    {
        $availableLayers = array_keys($this->layers);

        return max($availableLayers) === $index;
    }

    /**
     * @param int $index
     * @return string[]
     */
    public function getNextLowerLayer(int $index): array
    {
        // this is a simplification; should check next lower not just subtract one. but should be OK
        return $this->layers[$index - 1];
    }
}