<?php

namespace Agnes\Models;

class Build
{
    /**
     * @var string
     */
    private $commitish;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var string
     */
    private $content;

    /**
     * Build constructor.
     */
    public function __construct(string $commitish, string $hash, string $content)
    {
        $this->commitish = $commitish;
        $this->hash = $hash;
        $this->content = $content;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
