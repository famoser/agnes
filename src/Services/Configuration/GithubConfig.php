<?php

namespace Agnes\Services\Configuration;

class GithubConfig
{
    /**
     * GithubConfig constructor.
     */
    public function __construct(private string $apiToken, private string $repository)
    {
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
