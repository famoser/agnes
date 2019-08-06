<?php


namespace Agnes\Release;

use Agnes\Services\Configuration\GithubConfig;
use GuzzleHttp\Psr7\Request;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

class GithubService
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * ReleaseService constructor.
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param GithubConfig $githubConfig
     * @return \Agnes\Services\Github\Release[]
     * @throws Exception
     */
    public function releases(GithubConfig $githubConfig)
    {
        $response = $this->getReleases($githubConfig);
        $releases = json_decode($response);

        $parsedRelease = [];
        foreach ($releases as $release) {
            $name = $release->name;
            if (count($release->assets) > 0) {
                $assetId = $release->assets[0]->id;

                $parsedRelease[] = new \Agnes\Services\Github\Release($name, $assetId);
            }
        }

        return $parsedRelease;
    }

    /**
     * @param string $assetId
     * @param GithubConfig $githubConfig
     * @return string
     * @throws Exception
     */
    public function asset(string $assetId, GithubConfig $githubConfig)
    {
        $response = $this->downloadAsset($assetId, $githubConfig);

        return $response->getBody()->getContents();
    }

    /**
     * @param Release $release
     * @param GithubConfig $githubConfig
     * @throws Exception
     */
    public function publish(Release $release, GithubConfig $githubConfig)
    {
        $response = $this->createRelease($release, $githubConfig);

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int)$responseObject->id;

        $this->addReleaseAsset($releaseId, $release, $githubConfig);
    }

    /**
     * @param GithubConfig $config
     * @return ResponseInterface
     * @throws Exception
     * @throws \Exception
     */
    private function getReleases(GithubConfig $config): ResponseInterface
    {
        $request = new Request(
            'GET',
            'https://api.github.com/repos/' . $config->getRepository() . '/releases',
            ["Authorization" => "token " . $config->getApiToken()]
        );

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("GET of releases failed with status code " . $response->getStatusCode() . "\n" . $response->getBody());
        }

        return $response;
    }

    /**
     * @param int $assetId
     * @param GithubConfig $config
     * @return ResponseInterface
     * @throws Exception
     */
    private function downloadAsset(int $assetId, GithubConfig $config): ResponseInterface
    {
        $request = new Request(
            'GET',
            'https://api.github.com/repos/' . $config->getRepository() . '/releases/assets/' . $assetId,
            [
                "Authorization" => "token " . $config->getApiToken(),
                "Accept" => "application/octet-stream"
            ]
        );

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("GET of release failed with status code " . $response->getStatusCode() . "\n" . $response->getBody());
        }

        return $response;
    }

    /**
     * @param Release $release
     * @param GithubConfig $config
     * @return ResponseInterface
     * @throws Exception
     * @throws \Exception
     */
    private function createRelease(Release $release, GithubConfig $config): ResponseInterface
    {
        $request = new Request(
            'POST',
            'https://api.github.com/repos/' . $config->getRepository() . '/releases',
            ["Authorization" => "token " . $config->getApiToken()],
            '
            {
              "tag_name": "' . $release->getTagName() . '",
              "target_commitish": "' . $release->getTargetCommitish() . '",
              "name": "' . $release->getName() . '",
              "body": "' . $release->getDescription() . '",
              "draft": ' . $this->booleanToString($release->getDraft()) . ',
              "prerelease": ' . $this->booleanToString($release->getPrerelease()) . '
            }'
        );

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 201) {
            throw new \Exception("Creation of release failed with status code " . $response->getStatusCode() . "\n" . $response->getBody());
        }

        return $response;
    }

    /**
     * @param int $releaseId
     * @param GithubConfig $config
     * @return ResponseInterface
     * @throws Exception
     * @throws \Exception
     */
    private function deleteRelease(int $releaseId, GithubConfig $config)
    {
        $request = new Request(
            'DELETE',
            'https://api.github.com/repos/' . $config->getRepository() . '/releases/' . $releaseId,
            ["Authorization" => "token " . $config->getApiToken()]
        );

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 204) {
            throw new \Exception("Removal of release failed with status code " . $response->getStatusCode() . "\n" . $response->getBody());
        }

        return $response;
    }

    /**
     * @param bool $input
     * @return string
     */
    private function booleanToString(bool $input)
    {
        return $input ? "true" : "false";
    }

    /**
     * @param int $releaseId
     * @param Release $release
     * @param GithubConfig $config
     * @return ResponseInterface
     * @throws Exception
     * @throws \Exception
     */
    private function addReleaseAsset(int $releaseId, Release $release, GithubConfig $config): ResponseInterface
    {
        $request = new Request(
            'POST',
            'https://uploads.github.com/repos/' . $config->getRepository() . '/releases/' . $releaseId . "/assets?name=" . $release->getAssetName(),
            [
                "Authorization" => "token " . $config->getApiToken(),
                "Content-Type" => $release->getAssetContentType()
            ],
            $release->getAssetContent()
        );
        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 201) {
            throw new \Exception("Creation of release asset failed with status code " . $response->getStatusCode() . "\n" . $response->getBody());
        }

        return $response;
    }
}
