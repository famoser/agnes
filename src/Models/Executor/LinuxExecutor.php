<?php

namespace Agnes\Models\Executor;

class LinuxExecutor extends Executor
{
    public function mvSymlinkAtomicReplace(string $source, string $target): string
    {
        return "mv -T $source $target";
    }

    public function lnCreateSymbolicLink(string $filePath, string $destination): string
    {
        return "ln -s $destination $filePath";
    }
}
