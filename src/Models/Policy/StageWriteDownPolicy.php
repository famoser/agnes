<?php

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\PolicyVisitor;
use Exception;

class StageWriteDownPolicy extends LayeredPolicy
{
    /**
     * @return bool
     *
     * @throws Exception
     */
    public function accept(PolicyVisitor $visitor)
    {
        return $visitor->visitStageWriteDown($this);
    }
}
