<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Models\Executor;

class LinuxExecutor extends Executor
{
    public function mvSymlinkAtomicReplace(string $source, string $target): string
    {
        return "mv -T $source $target";
    }
}
