<?php


namespace Agnes\Models\Policies;


use Agnes\Models\Tasks\Filter;

abstract class LayeredPolicy extends Policy
{
    /**
     * @var string[]
     */
    private $layers;

    /**
     * LayeredPolicy constructor.
     * @param Filter|null $filter
     * @param string[] $layers
     */
    public function __construct(?Filter $filter, array $layers)
    {
        parent::__construct($filter);

        $this->layers = $layers;
    }

    /**
     * @return string[]
     */
    public function getLayers(): array
    {
        return $this->layers;
    }
}