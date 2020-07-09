<?php

namespace Agnes\Actions;

use Agnes\Actions\Visitors\AbstractActionVisitor;

class Release extends AbstractPayload
{
    /**
     * @var string
     */
    private $name;

    /**
     * Release constructor.
     *
     * @param string $name
     */
    public function __construct(string $commitish, string $name = null)
    {
        $this->name = $name;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getName(): string
    {
        return null !== $this->name ? $this->name : $this->commitish;
    }

    public function describe(): string
    {
        return 'release '.$this->getName();
    }

    public function accept(AbstractActionVisitor $abstractActionVisitor): bool
    {
        return $abstractActionVisitor->visitRelease($this);
    }
}
