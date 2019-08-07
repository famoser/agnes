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
     * @param string $server
     * @param string $environment
     * @param string $stage
     * @return bool
     */
    public function isApplicable(string $server, string $environment, string $stage)
    {
        return $this->filter === null || $this->filter->isMatch($server, $environment, $stage);
    }

    /**
     * @param PolicyVisitor $visitor
     * @return bool
     */
    public abstract function accept(PolicyVisitor $visitor);
}