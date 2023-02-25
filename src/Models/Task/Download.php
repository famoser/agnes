<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

class Download extends AbstractTask
{
    public const TYPE = 'download';

    /**
     * @var string
     */
    private $commitish;

    /**
     * @var string
     */
    private $release;

    /**
     * DownloadGithub constructor.
     */
    public function __construct(string $commitish, string $release)
    {
        $this->commitish = $commitish;
        $this->release = $release;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getRelease(): string
    {
        return $this->release;
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitDownload($this);
    }

    public function describe(): string
    {
        return 'download asset of release '.$this->release;
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
