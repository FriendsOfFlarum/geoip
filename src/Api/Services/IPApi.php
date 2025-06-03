<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Services;

use Carbon\Carbon;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\ServiceResponse;
use Psr\Http\Message\ResponseInterface;

class IPApi extends BaseGeoService
{
    protected $host = 'http://ip-api.com';
    protected $settingPrefix = 'fof-geoip.services.ipapi';
    protected $requestFields = 'status,message,countryCode,region,regionName,city,zip,lat,lon,isp,org,as,mobile,query';
    protected $r2 = '126718';

    /**
     * 45 requests per minute.
     *
     * @see https://ip-api.com/docs/api:json
     *
     * @var int
     */
    protected int $singleLookupsRemaining = 45;

    /**
     * 15 requests per minute.
     *
     * @see https://ip-api.com/docs/api:batch
     *
     * @var int
     */
    protected int $batchLookupsRemaining = 15;

    protected function updateRateLimitsFromResponse(ResponseInterface $response, string $requestType = 'single'): void
    {
        /**
         * The number of requests remaining in the current time window.
         *
         * @var int
         */
        $remaining = (int) $response->getHeaderLine('X-Rl');

        /**
         * The number of seconds until the current time window resets.
         *
         * @var int
         */
        $ttl = Carbon::now()->addSeconds((int) $response->getHeaderLine('X-Ttl'));

        // Cache the remaining requests for the current time window
        $this->cache->put("$this->settingPrefix.$requestType", $remaining, $ttl);
    }

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        return "/json/{$ip}";
    }

    protected function buildBatchUrl(array $ips, ?string $apiKey): string
    {
        return '/batch';
    }

    protected function requiresApiKey(): bool
    {
        return false;
    }

    protected function getRequestOptions(?string $apiKey, ?array $ips = null): array
    {
        $options = [];

        $options['http_errors'] = false;
        $options['query'] = [
            'fields' => $this->requestFields,
        ];

        if ($ips && is_array($ips)) {
            // array is key => value, we only want values, then encode to json
            $ips = array_values($ips);

            $options['json'] = $ips;
        }

        return $options;
    }

    protected function hasError(ResponseInterface $response, mixed $body): bool
    {
        $allowedFailures = ['reserved range', 'private range'];

        return $body?->status === 'fail' && !in_array($body->message, $allowedFailures);
    }

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipapi', $body->message ?? json_encode($body));
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        $response = new ServiceResponse($this->host);
        $response->setIP($body->query);

        if (property_exists($body, 'message') && !empty($body->message)) {
            return $response->setIsp($body->message)
                ->setOrganization($body->message);
        }

        return $response->setCountryCode($body->countryCode)
            ->setZipCode($body->zip)
            ->setLatitude($body->lat)
            ->setLongitude($body->lon)
            ->setIsp($body->isp)
            ->setOrganization($body->org)
            ->setAs($body->as)
            ->setMobile($body->mobile);
    }

    public function batchSupported(): bool
    {
        return true;
    }

    public function isRateLimited(): bool
    {
        return true;
    }

    protected function parseBatchResponse(array $responses): array
    {
        $ipDataCollection = [];

        foreach ($responses as $response) {
            $ipDataCollection[] = $this->parseResponse($response);
        }

        return $ipDataCollection;
    }
}
