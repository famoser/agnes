<?php

namespace Agnes\Services\Policy;

use Agnes\Actions\Rollback;
use Agnes\Models\Filter;
use Symfony\Component\Console\Style\StyleInterface;

class RollbackPolicyVisitor extends PolicyVisitor
{
    /**
     * @var Rollback
     */
    private $rollback;

    /**
     * RollbackVisitor constructor.
     */
    public function __construct(StyleInterface $io, Rollback $rollback)
    {
        parent::__construct($io);

        $this->rollback = $rollback;
    }

    /**
     * checks if the policy has to be checked for.
     *
     * @param Filter $filter
     *
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return null === $filter || $filter->instanceMatches($this->rollback->getInstance());
    }
}
