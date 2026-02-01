<?php

namespace Agnes\Services\Task\ExecutionVisitor;

readonly class BuildResult
{
    public function __construct(private string $commitish, private string $releaseOrHash, private string $content)
    {
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
