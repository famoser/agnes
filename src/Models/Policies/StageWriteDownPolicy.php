<?php


namespace Agnes\Models\Policies;


use Agnes\Services\Policy\PolicyVisitor;

class StageWriteDownPolicy extends LayeredPolicy
{
    /**
     * @param PolicyVisitor $visitor
     * @return bool
     * @throws \Exception
     */
    public function accept(PolicyVisitor $visitor)
    {
        return $visitor->visitStageWriteDown($this);
    }
}