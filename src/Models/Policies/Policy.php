<?php


namespace Agnes\Models\Policies;


use Agnes\Models\Tasks\Filter;
use Agnes\Services\Policy\PolicyVisitor;

abstract class Policy
{
    /**
     * @var Filter|null
     */
    private $filter;

    /**
     * Policy constructor.
     * @param Filter|null $filter
     */
    public function __construct(?Filter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @param PolicyVisitor $visitor
     * @return bool
     * @throws \Exception
     */
    public abstract function accept(PolicyVisitor $visitor);

    /**
     * @return Filter|null
     */
    public function getFilter(): ?Filter
    {
        return $this->filter;
    }
}