<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Models\Policy;

use Agnes\Models\Filter;

abstract class LayeredPolicy extends Policy
{
    /**
     * @var string[][]
     */
    private $layers = [];

    /**
     * LayeredPolicy constructor.
     *
     * @param string[][] $layers
     */
    public function __construct(string $name, ?Filter $filter, array $layers)
    {
        parent::__construct($name, $filter);

        foreach ($layers as $key => $entries) {
            $this->layers[(int) $key] = $entries;
        }

        ksort($this->layers);
    }

    /**
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
     * @return string[]
     */
    public function getLayer(int $stageIndex)
    {
        return $this->layers[$stageIndex];
    }

    /**
     * @return bool
     */
    public function isLowestLayer(int $index)
    {
        $availableLayers = array_keys($this->layers);

        return min($availableLayers) === $index;
    }

    /**
     * @return bool
     */
    public function isHighestLayer(string $index)
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
