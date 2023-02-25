<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Github;

use Agnes\Services\Configuration\GithubConfig;
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

    public function getReleases(): ResponseInterface
    {
        return $this->executeRequest(
            'GET',
            'https://api.github.com/repos/'.$this->githubConfig->getRepository().'/releases',
            200
        );
    }

    public function downloadAsset(int $assetId): ResponseInterface
    {
        return $this->executeRequest(
            'GET', 'https://api.github.com/repos/'.$this->githubConfig->getRepository().'/releases/assets/'.$assetId,
            200,
            ['Accept' => 'application/octet-stream']
        );
    }

    public function createRelease(string $releaseContent): ResponseInterface
    {
        return $this->executeRequest(
            'POST', 'https://api.github.com/repos/'.$this->githubConfig->getRepository().'/releases',
            201,
            [],
            $releaseContent
        );
    }

    public function deleteRelease(int $releaseId): ResponseInterface
    {
        return $this->executeRequest(
            'DELETE', 'https://api.github.com/repos/'.$this->githubConfig->getRepository().'/releases/'.$releaseId, 204
        );
    }

    public function addReleaseAsset(int $releaseId, string $assetName, string $assetContentType, string $assetContent): ResponseInterface
    {
        return $this->executeRequest(
            'POST', 'https://uploads.github.com/repos/'.$this->githubConfig->getRepository().'/releases/'.$releaseId.'/assets?name='.$assetName,
            201,
            ['Content-Type' => $assetContentType],
            $assetContent
        );
    }

    private function executeRequest(string $method, string $url, int $expectedStatusCode, array $additionalHeaders = [], string $body = null): ResponseInterface
    {
        $headers = array_merge([
            'Authorization' => 'token '.$this->githubConfig->getApiToken(),
            'Accept' => 'application/vnd.github.v3+json',
        ], $additionalHeaders);

        $options = ['headers' => $headers];
        if (null !== $body) {
            $options['body'] = $body;
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $url, $options);

        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new \Exception("Request failed: $method $url with status code ".$response->getStatusCode()."\n".$response->getBody());
        }

        return $response;
    }
}
