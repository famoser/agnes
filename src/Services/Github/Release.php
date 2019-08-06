<?php


namespace Agnes\Services\Github;


class Release
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $assetId;

    /**
     * Release constructor.
     * @param string $name
     * @param int $assetId
     */
    public function __construct(string $name, int $assetId)
    {
        $this->name = $name;
        $this->assetId = $assetId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getAssetId(): int
    {
        return $this->assetId;
    }
}