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
     * @param bool $required
     * @param string $path
     */
    public function __construct(bool $required, string $path)
    {
        $this->required = $required;
        $this->path = $path;
    }

    /**
     * @return bool
     */
    public function getIsRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}