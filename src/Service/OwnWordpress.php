<?php

declare(strict_types=1);

namespace App\Service;

use Happyr\WordpressBundle\Api\WpClient;
use App\Api\WpClient as MyWpClient;
use Happyr\WordpressBundle\Model\Menu;
use Happyr\WordpressBundle\Model\Page;
use Happyr\WordpressBundle\Parser\MessageParser;
use PhpParser\Node\Expr\Array_;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * This is the class you want to interact with. It fetches data from
 * cache or API.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class OwnWordpress
{
    private $cache;
    private $client;
    private $myClient;
    private $messageParser;
    private $ttl;

    public function __construct(WpClient $client, MyWpClient $myWpClient, MessageParser $parser, CacheInterface $cache)
    {
        $this->client = $client;
        $this->myClient = $myWpClient;
        $this->messageParser = $parser;
        $this->cache = $cache;
        $this->ttl = 300;
    }

    public function getOwnMenu(string $slug): ?Array
    {
        return $this->cache->get($this->getCacheKey('menu', $slug), function (ItemInterface $item) use ($slug) {
            $data = $this->myClient->getMyOwnMenu($slug);
            if (!$this->isValidResponse($data)) {
                $item->expiresAfter(300);

                return null;
            }

            $item->expiresAfter($this->ttl);

            return $this->rewrite($data);
        });
    }

    /**
     * This requires the https://wordpress.org/plugins/tutexp-rest-api-menu/ to be installed.
     */
    public function getMenu(string $slug): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->baseUrl.'/wp-api-menus/v2/menus/'.$slug);
        $response = $this->httpClient->sendRequest($request);

        $data = $this->jsonDecode($response);
        if (count($data) >= 1) {
            return $data;
        }

        return [];
    }

    private function rewrite(Array $menu): Array
    {
        foreach ($menu['items'] as $key=>$item){
            $url = $item['url'];
            if(strpos($url, 'wp-json') === false){
                $url = str_replace($_ENV['WP_URL'], '/', $url);
                $url = str_replace($_ENV['WP_URL'], '/', $url);
                $menu['items'][$key]['url'] = $url;
            }
        }
        return $menu;
    }

    private function getCacheKey(string $prefix, string $identifier): string
    {
        return sha1($prefix.'_'.$identifier);
    }

    private function isValidResponse($data): bool
    {
        if (isset($data['code']) && isset($data['data']['status']) && 400 === $data['data']['status']) {
            return false;
        }

        return !empty($data);
    }
}
