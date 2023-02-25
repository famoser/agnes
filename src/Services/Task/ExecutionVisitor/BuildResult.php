<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Task\ExecutionVisitor;

class BuildResult
{
    /**
     * @var string
     */
    private $commitish;

    /**
     * @var string
     */
    private $releaseOrHash;

    /**
     * @var string
     */
    private $content;

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
