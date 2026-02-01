<?php

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\AbstractPolicyVisitor;

class StageWriteUpPolicy extends LayeredPolicy
{
    /**
     *
     */
    public function accept(AbstractPolicyVisitor $visitor): bool
    {
        return $visitor->visitStageWriteUp($this);
    }
}
