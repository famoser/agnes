<?php

namespace Agnes\Services\Configuration;

readonly class File
{
    public function __construct(private bool $required, private bool $encrypted, private string $path)
    {
    }

    public function getIsRequired(): bool
    {
        return $this->required;
    }

    public function getIsEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
