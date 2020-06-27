<?php

namespace Agnes\Models\Executors;

class LinuxExecutor extends Executor
{
    public function replaceSymlink(string $source, string $target): string
    {
        return "mv -T $source $target";
    }
}
