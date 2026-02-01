<?php

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\AbstractPolicyVisitor;

class SameReleasePolicy extends Policy
{
    /**
     * @throws \Exception
     */
    public function accept(AbstractPolicyVisitor $visitor): bool
    {
        return $visitor->visitSameRelease($this);
    }
}
