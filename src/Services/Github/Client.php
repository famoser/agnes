<?php


namespace Agnes\Services\Github;


use Agnes\Services\Configuration\GithubConfig;
use GuzzleHttp\Psr7\Request;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var GithubConfig
     */
    private $githubConfig;

    /**
     * Client constructor.
     * @param HttpClient $httpClient
     * @param GithubConfig $githubConfig
     */
    public function __construct(HttpClient $httpClient, GithubConfig $githubConfig)
    {
        $this->httpClient = $httpClient;
        $this->githubConfig = $githubConfig;
    }


    /**
     * @return ResponseInterface
     * @throws Exception
     */
    public function getReleases(): ResponseInterface
    {
        return $this->executeRequest(
            "GET",
            'https://api.github.com/repos/' . $this->githubConfig->getRepository() . '/releases',
            200
        );
    }

    /**
     * @param int $assetId
     * @return ResponseInterface
     * @throws Exception
     */
    public function downloadAsset(int $assetId): ResponseInterface
    {
        return $this->executeRequest(
            "GET", 'https://api.github.com/repos/' . $this->githubConfig->getRepository() . '/releases/assets/' . $assetId,
            200,
            ["Accept" => "application/octet-stream"]
        );
    }

    /**
     * @param string $releaseContent
     * @return ResponseInterface
     * @throws Exception
     */
    public function createRelease(string $releaseContent): ResponseInterface
    {
        return $this->executeRequest(
            "POST", 'https://api.github.com/repos/' . $this->githubConfig->getRepository() . '/releases',
            201,
            [],
            $releaseContent
        );
    }

    /**
     * @param int $releaseId
     * @return ResponseInterface
     * @throws Exception
     */
    public function deleteRelease(int $releaseId)
    {
        return $this->executeRequest(
            "DELETE", 'https://api.github.com/repos/' . $this->githubConfig->getRepository() . '/releases/' . $releaseId, 204
        );
    }

    /**
     * @param int $releaseId
     * @param string $assetName
     * @param string $assetContentType
     * @param string $assetContent
     * @return ResponseInterface
     * @throws Exception
     */
    public function addReleaseAsset(int $releaseId, string $assetName, string $assetContentType, string $assetContent): ResponseInterface
    {
        return $this->executeRequest(
            "POST", 'https://uploads.github.com/repos/' . $this->githubConfig->getRepository() . '/releases/' . $releaseId . "/assets?name=" . $assetName,
            201,
            ["Content-Type" => $assetContentType],
            $assetContent
        );
    }


    /**
     * @param string $method
     * @param string $url
     * @param int $expectedStatusCode
     * @param array $additionalHeaders
     * @param string|null $body
     * @return ResponseInterface
     * @throws Exception
     * @throws \Exception
     */
    private function executeRequest(string $method, string $url, int $expectedStatusCode, array $additionalHeaders = [], string $body = null)
    {
        $headers = [
            "Authorization" => "token " . $this->githubConfig->getApiToken(),
            "Accept" => "application/vnd.github.v3+json"
        ];
        $headers = array_merge($headers, $additionalHeaders);
        $request = new Request($method, $url, $headers, $body);

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw new \Exception("Request failed: $method $url with status code " . $response->getStatusCode() . "\n" . $response->getBody());
        }

        return $response;
    }
}