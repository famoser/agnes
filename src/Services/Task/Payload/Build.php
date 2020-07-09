<?php

namespace Agnes\Models;

use Agnes\Actions\AbstractPayload;
use Agnes\Actions\Visitors\AbstractActionVisitor;

class Build extends AbstractPayload
{
    /**
     * @var string
     */
    private $commitish;

    /**
     * Build constructor.
     */
    public function __construct(string $commtish)
    {
        $this->commitish = $commtish;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function accept(AbstractActionVisitor $abstractActionVisitor): bool
    {
        return $abstractActionVisitor->visitBuild($this);
    }

    public function describe(): string
    {
        return 'build '.$this->commitish;
    }
}
