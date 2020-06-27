<?php

namespace Agnes\Models\Executors;

class BSDExecutor extends Executor
{
    public function replaceSymlink(string $source, string $target): string
    {
        return "mv -h $source $target";
    }
}
