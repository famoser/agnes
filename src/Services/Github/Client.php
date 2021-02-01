<?php

namespace Agnes\Services\Github;

use Agnes\Services\Configuration\GithubConfig;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @var GithubConfig
     */
    private $githubConfig;

    /**
     * Client constructor.
     */
    public function __construct(GithubConfig $githubConfig)
    {
        $this->githubConfig = $githubConfig;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getReleases(): ResponseInterface
    {
        return $this->executeRequest(
            'GET',
            'https://api.github.com/repos/'.$this->githubConfig->getRepository().'/releases',
            200
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function downloadAsset(int $assetId): ResponseInterface
    {
        return $this->executeRequest(
            'GET', 'https://api.github.com/repos/'.$this->githubConfig->getRepository().'/releases/assets/'.$assetId,
            200,
            ['Accept' => 'application/octet-stream']
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function createRelease(string $releaseContent): ResponseInterface
    {
        return $this->executeRequest(
            'POST', 'https://api.github.com/repos/'.$this->githubConfig->getRepository().'/releases',
            201,
            [],
            $releaseContent
        );
    }

    /**
     * @return ResponseInterface
     *
     * @throws ClientExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function deleteRelease(int $releaseId)
    {
        return $this->executeRequest(
            'DELETE', 'https://api.github.com/repos/'.$this->githubConfig->getRepository().'/releases/'.$releaseId, 204
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function addReleaseAsset(int $releaseId, string $assetName, string $assetContentType, string $assetContent): ResponseInterface
    {
        return $this->executeRequest(
            'POST', 'https://uploads.github.com/repos/'.$this->githubConfig->getRepository().'/releases/'.$releaseId.'/assets?name='.$assetName,
            201,
            ['Content-Type' => $assetContentType],
            $assetContent
        );
    }

    /**
     * @return ResponseInterface
     *
     * @throws \Exception
     * @throws ClientExceptionInterface
     */
    private function executeRequest(string $method, string $url, int $expectedStatusCode, array $additionalHeaders = [], string $body = null)
    {
        $headers = array_merge([
            'Authorization' => 'token '.$this->githubConfig->getApiToken(),
            'Accept' => 'application/vnd.github.v3+json',
        ], $additionalHeaders);

        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $url, ['headers' => $headers]);

        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new \Exception("Request failed: $method $url with status code ".$response->getStatusCode()."\n".$response->getBody());
        }

        return $response;
    }
}
