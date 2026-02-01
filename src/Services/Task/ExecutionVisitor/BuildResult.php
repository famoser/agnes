<?php

namespace Agnes\Services\Task\ExecutionVisitor;

readonly class BuildResult
{
    private string $commitish;

    private string $releaseOrHash;

    private string $content;

    /**
     * BuildResult constructor.
     */
    public function __construct(string $commitish, string $releaseOrHash, string $content)
    {
        $this->commitish = $commitish;
        $this->releaseOrHash = $releaseOrHash;
        $this->content = $content;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getReleaseOrHash(): string
    {
        return $this->releaseOrHash;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
