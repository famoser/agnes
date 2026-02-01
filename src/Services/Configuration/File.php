<?php

namespace Agnes\Services\Configuration;

class File
{
    private bool $required;

    private string $path;

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
