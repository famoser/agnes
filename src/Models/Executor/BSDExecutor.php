<?php

namespace Agnes\Models\Executor;

class BSDExecutor extends Executor
{
    public function mvSymlinkAtomicReplace(string $source, string $target): string
    {
        return "mv -h $source $target";
    }
}
