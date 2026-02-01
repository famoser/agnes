<?php

namespace Agnes\Services\Configuration;

class File
{
    /**
     * @var bool
     */
    private $required;

    /**
     * @var string
     */
    private $path;

    /**
     * File constructor.
     */
    public function __construct(bool $required, string $path)
    {
        $this->required = $required;
        $this->path = $path;
    }

    public function getIsRequired(): bool
    {
        return $this->required;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
