<?php


namespace Agnes\Models\Policies;


use Agnes\Models\Tasks\Filter;
use Agnes\Services\Policy\PolicyVisitor;
use Exception;

class ReleaseWhitelistPolicy extends Policy
{
    /**
     * @var string[]
     */
    private $commitishes;

    /**
     * ReleaseWhitelistPolicy constructor.
     * @param Filter|null $filter
     * @param array $commitishes
     */
    public function __construct(?Filter $filter, array $commitishes)
    {
        parent::__construct($filter);

        $this->commitishes = $commitishes;
    }

    /**
     * @param PolicyVisitor $visitor
     * @return bool
     * @throws Exception
     */
    public function accept(PolicyVisitor $visitor)
    {
        return $visitor->visitReleaseWhitelist($this);
    }

    /**
     * @return string[]
     */
    public function getCommitishes(): array
    {
        return $this->commitishes;
    }
}