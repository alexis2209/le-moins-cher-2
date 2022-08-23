<?php

declare(strict_types=1);

namespace App\Api;

use Happyr\WordpressBundle\Model\Menu;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A super simple API client.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class WpClient
{
    private $baseUrl;
    private $httpClient;
    private $requestFactory;

    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory, string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->requestFactory = $requestFactory;
        $this->httpClient = $httpClient;
    }

    /**
     * This requires the https://wordpress.org/plugins/tutexp-rest-api-menu/ to be installed.
     */
    public function getMyOwnMenu(string $slug): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->baseUrl.'/wp-api-menus/v2/menus/'.$slug);
        $response = $this->httpClient->sendRequest($request);


        $data = $this->jsonDecode($response);
        if (count($data) >= 1) {
            return $data;
        }
        return [];
    }


    /**
     * This requires the https://wordpress.org/plugins/tutexp-rest-api-menu/ to be installed.
     */
    public function getOwnCateg(): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->baseUrl.'/wp/v2/categories');
        $response = $this->httpClient->sendRequest($request);


        $data = $this->jsonDecode($response);
        if (count($data) >= 1) {
            return $data;
        }
        return [];
    }

    private function jsonDecode(ResponseInterface $response): array
    {
        $body = $response->getBody()->__toString();
        $contentType = $response->getHeaderLine('Content-Type');
        if (0 !== strpos($contentType, 'application/json') && 0 !== strpos($contentType, 'application/octet-stream')) {
            throw new \RuntimeException('The ModelHydrator cannot hydrate response with Content-Type: '.$contentType);
        }
        $data = json_decode($body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(sprintf('Error (%d) when trying to json_decode response', json_last_error()));
        }

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }
}
