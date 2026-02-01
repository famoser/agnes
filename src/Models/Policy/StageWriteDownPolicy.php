<?php

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\AbstractPolicyVisitor;

class StageWriteDownPolicy extends LayeredPolicy
{
    /**
     * @throws \Exception
     */
    public function accept(AbstractPolicyVisitor $visitor): bool
    {
        return $visitor->visitStageWriteDown($this);
    }
}
