<?php

namespace Agnes\Models\Policy;

use Agnes\Models\Filter;

abstract readonly class LayeredPolicy extends Policy
{
    /**
     * @var string[][]
     */
    private array $layers;

    /**
     * LayeredPolicy constructor.
     *
     * @param string[][] $layers
     */
    public function __construct(string $name, ?Filter $filter, array $layers)
    {
        parent::__construct($name, $filter);

        $sortedLayers = [];
        foreach ($layers as $key => $entries) {
            $sortedLayers[(int) $key] = $entries;
        }

        ksort($sortedLayers);
        $this->layers = $sortedLayers;
    }

    public function getLayerIndex(string $value): int|false
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
     * @return string[]
     */
    public function getLayer(int $stageIndex): array
    {
        return $this->layers[$stageIndex];
    }

    public function isLowestLayer(int $index): bool
    {
        $availableLayers = array_keys($this->layers);

        return min($availableLayers) === $index;
    }

    public function isHighestLayer(int $index): bool
    {
        $availableLayers = array_keys($this->layers);

        return max($availableLayers) === $index;
    }

    /**
     * @return string[]
     */
    public function getNextLowerLayer(int $index): array
    {
        // this is a simplification; should check next lower not just subtract one. but should be OK
        return $this->layers[$index - 1];
    }
}
