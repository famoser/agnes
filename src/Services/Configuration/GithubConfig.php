<?php

namespace Agnes\Services\Configuration;

class GithubConfig
{
    /**
     * @var string
     */
    private $apiToken;

    /**
     * @var string
     */
    private $repository;

    /**
     * GithubConfig constructor.
     */
    public function __construct(string $apiToken, string $repository)
    {
        $this->apiToken = $apiToken;
        $this->repository = $repository;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }
}
