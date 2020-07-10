<?php

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\AbstractPolicyVisitor;
use Exception;

class StageWriteDownPolicy extends LayeredPolicy
{
    /**
     * @return bool
     *
     * @throws Exception
     */
    public function accept(AbstractPolicyVisitor $visitor)
    {
        return $visitor->visitStageWriteDown($this);
    }
}
