<?php

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\AbstractPolicyVisitor;
use Exception;

class StageWriteUpPolicy extends LayeredPolicy
{
    /**
     * @return bool
     *
     * @throws Exception
     */
    public function accept(AbstractPolicyVisitor $visitor)
    {
        return $visitor->visitStageWriteUp($this);
    }
}
