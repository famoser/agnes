<?php

namespace Agnes\Models\Executor;

class LinuxExecutor extends Executor
{
    public function replaceSymlink(string $source, string $target): string
    {
        return "mv -T $source $target";
    }
}
