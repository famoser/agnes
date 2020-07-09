<?php

namespace Agnes\Models\Executor;

class BSDExecutor extends Executor
{
    public function replaceSymlink(string $source, string $target): string
    {
        return "mv -h $source $target";
    }
}
