<?php


namespace Agnes\Models\Executors;


class LinuxExecutor extends Executor
{
    /**
     * @param string $source
     * @param string $target
     * @return string
     */
    public function moveAndReplace(string $source, string $target): string
    {
        return "mv -Tf $source $target";
    }
}