<?php

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\AbstractPolicyVisitor;

readonly class StageWriteDownPolicy extends LayeredPolicy
{
    public function accept(AbstractPolicyVisitor $visitor): bool
    {
        return $visitor->visitStageWriteDown($this);
    }
}
