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
     * @param string $apiToken
     * @param string $repository
     */
    public function __construct(string $apiToken, string $repository)
    {
        $this->apiToken = $apiToken;
        $this->repository = $repository;
    }

    /**
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    /**
     * @return string
     */
    public function getRepository(): string
    {
        return $this->repository;
    }
}