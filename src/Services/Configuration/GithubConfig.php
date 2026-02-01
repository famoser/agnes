<?php

namespace Agnes\Services\Configuration;

readonly class GithubConfig
{
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
