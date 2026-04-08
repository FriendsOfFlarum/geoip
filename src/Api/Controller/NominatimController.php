<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Controller;

use Flarum\Http\RequestUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Proxies Nominatim API requests server-side so the admin's browser IP is not
 * exposed to OpenStreetMap, and so Nominatim receives a proper User-Agent and
 * Referer identifying this installation (as required by the Nominatim usage policy).
 */
class NominatimController implements RequestHandlerInterface
{
    private const NOMINATIM_BASE = 'https://nominatim.openstreetmap.org';
    private const CONNECT_TIMEOUT = 5;
    private const REQUEST_TIMEOUT = 10;
    private const MAX_RESPONSE_BYTES = 512 * 1024; // 512 KB
    private const CACHE_TTL = 7 * 24 * 60 * 60;   // 7 days — matches OSM tile caching guidance

    public function __construct(protected Cache $cache)
    {
        $this->client = new Client([
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'timeout'         => self::REQUEST_TIMEOUT,
            'http_errors'     => false,
        ]);
    }

    private Client $client;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertPermission($actor->can('discussion.viewIpsPosts'));

        $params = $request->getQueryParams();
        $type = Arr::get($params, 'type', '');

        return match ($type) {
            'reverse' => $this->reverse($params),
            'search'  => $this->search($params),
            default   => new JsonResponse([
                'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'Invalid type parameter. Use "reverse" or "search".']],
            ], 400),
        };
    }

    private function reverse(array $params): ResponseInterface
    {
        $lat = Arr::get($params, 'lat');
        $lon = Arr::get($params, 'lon');

        if (!is_numeric($lat) || !is_numeric($lon)) {
            return new JsonResponse([
                'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'lat and lon must be numeric.']],
            ], 400);
        }

        $lat = (float) $lat;
        $lon = (float) $lon;

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return new JsonResponse([
                'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'lat must be between -90 and 90, lon between -180 and 180.']],
            ], 400);
        }

        return $this->nominatimRequest('/reverse', [
            'lat'    => $lat,
            'lon'    => $lon,
            'format' => 'json',
        ]);
    }

    private function search(array $params): ResponseInterface
    {
        $q = trim((string) Arr::get($params, 'q', ''));
        $countrycodes = trim((string) Arr::get($params, 'countrycodes', ''));

        if ($q === '') {
            return new JsonResponse([
                'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'q parameter is required.']],
            ], 400);
        }

        if (strlen($q) > 200) {
            return new JsonResponse([
                'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'q parameter is too long.']],
            ], 400);
        }

        $query = [
            'q'      => $q,
            'limit'  => 1,
            'format' => 'json',
        ];

        if ($countrycodes !== '') {
            // Validate: ISO 3166-1 alpha-2 codes only (comma-separated)
            if (!preg_match('/^[a-zA-Z]{2}(,[a-zA-Z]{2})*$/', $countrycodes)) {
                return new JsonResponse([
                    'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'countrycodes must be comma-separated ISO 3166-1 alpha-2 codes.']],
                ], 400);
            }
            $query['countrycodes'] = strtolower($countrycodes);
        }

        return $this->nominatimRequest('/search', $query);
    }

    private function nominatimRequest(string $path, array $query): ResponseInterface
    {
        $cacheKey = 'fof-geoip.nominatim.'.md5($path.serialize($query));

        if ($cached = $this->cache->get($cacheKey)) {
            return new JsonResponse($cached);
        }

        try {
            $response = $this->client->get(self::NOMINATIM_BASE.$path, [
                'query'   => $query,
                'headers' => [
                    'User-Agent' => 'fof/geoip Flarum extension (https://github.com/FriendsOfFlarum/geoip)',
                    'Referer'    => self::NOMINATIM_BASE,
                ],
            ]);

            $status = $response->getStatusCode();

            if ($status === 429) {
                return new JsonResponse([
                    'errors' => [['status' => '429', 'title' => 'Too Many Requests', 'detail' => 'Nominatim rate limit reached. Please try again later.']],
                ], 429);
            }

            if ($status < 200 || $status >= 300) {
                return new JsonResponse([
                    'errors' => [['status' => '502', 'title' => 'Bad Gateway', 'detail' => "Nominatim returned HTTP {$status}."]],
                ], 502);
            }

            $body = $response->getBody()->read(self::MAX_RESPONSE_BYTES);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'errors' => [['status' => '502', 'title' => 'Bad Gateway', 'detail' => 'Invalid JSON response from Nominatim.']],
                ], 502);
            }

            $this->cache->put($cacheKey, $data, self::CACHE_TTL);

            return new JsonResponse($data);
        } catch (ConnectException) {
            return new JsonResponse([
                'errors' => [['status' => '504', 'title' => 'Gateway Timeout', 'detail' => 'Could not connect to Nominatim.']],
            ], 504);
        } catch (GuzzleException) {
            return new JsonResponse([
                'errors' => [['status' => '502', 'title' => 'Bad Gateway', 'detail' => 'Nominatim request failed.']],
            ], 502);
        }
    }
}
