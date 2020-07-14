<?php

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\AbstractPolicyVisitor;
use Exception;

class SameReleasePolicy extends Policy
{
    /**
     * @return bool
     *
     * @throws Exception
     */
    public function accept(AbstractPolicyVisitor $visitor)
    {
        return $visitor->visitSameRelease($this);
    }
}
