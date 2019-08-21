<?php


namespace Agnes\Models\Executors;


class BSDExecutor extends Executor
{
    /**
     * @param string $source
     * @param string $target
     * @return string
     */
    public function replaceSymlink(string $source, string $target): string
    {
        return "mv -h $source $target";
    }
}