<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Models\Policy;

use Agnes\Services\Policy\AbstractPolicyVisitor;

class SameReleasePolicy extends Policy
{
    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function accept(AbstractPolicyVisitor $visitor)
    {
        return $visitor->visitSameRelease($this);
    }
}
