<?php


namespace Agnes\Models\Policies;


use Agnes\Services\Policy\PolicyVisitor;
use Exception;

class StageWriteUpPolicy extends LayeredPolicy
{
    /**
     * @param PolicyVisitor $visitor
     * @return bool
     * @throws Exception
     */
    public function accept(PolicyVisitor $visitor)
    {
        return $visitor->visitStageWriteUp($this);
    }
}