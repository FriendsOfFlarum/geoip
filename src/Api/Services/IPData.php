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
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class IPData extends BaseGeoService
{
    protected $host = 'https://api.ipdata.co';
    protected $settingPrefix = 'fof-geoip.services.ipdata';

    /**
     * 1500 lookups per day, on the free plan.
     *
     * @see https://ipdata.co/pricing.html
     *
     * @var int
     */
    protected int $singleLookupsRemaining = 1500;

    protected function updateRateLimitsFromResponse(ResponseInterface $response, string $requestType = 'single'): void
    {
        /**
         * The number of requests remaining in the current time window.
         *
         * @var int
         */
        $remaining = 1500 - (int) Arr::get(json_decode($response->getBody(), true), 'count', $this->cache->get("$this->settingPrefix.$requestType"));

        $ttlKey = "$this->settingPrefix.$requestType.ttl";
        $ttl = $this->cache->get($ttlKey);

        if (!$ttl) {
            $ttl = Carbon::now()->addHours(24);
            $this->cache->put($ttlKey, $ttl, $ttl);
        }

        // Cache the remaining requests for the current time window
        $this->cache->put("$this->settingPrefix.$requestType", $remaining, $ttl);
    }

    public function isRateLimited(): bool
    {
        return true;
    }

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        return "/{$ip}";
    }

    protected function buildBatchUrl(array $ips, ?string $apiKey): string
    {
        // Not currently implemented
        return '';
    }

    protected function getRequestOptions(?string $apiKey, ?array $ips = null): array
    {
        return [
            'http_errors' => false,
            'delay'       => 100,
            'retries'     => 3,
            'query'       => [
                'fields'  => 'count,country_code,postal,asn,threat,carrier,latitude,longitude',
                'api-key' => $apiKey,
            ],
        ];
    }

    protected function hasError(ResponseInterface $response, mixed $body): bool
    {
        return isset($body?->error) || ($response->getStatusCode() >= 400 && !Str::contains($body?->message, ['is a reserved IP address']));
    }

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipdata', $body->error->message ?? json_encode($body));
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        $response = new ServiceResponse($this->host);

        if (property_exists($body, 'message') && !empty($body->message)) {
            return $response->setIsp($body->message)
                ->setOrganization($body->message);
        }

        $response->setCountryCode($body->country_code)
            ->setZipCode($body->postal)
            ->setLatitude($body->latitude)
            ->setLongitude($body->longitude)
            ->setThreatLevel($body->threat->is_threat)
            ->setThreatType($body->threat->is_known_attacker ? 'attacker' : ($body->threat->is_known_abuser ? 'abuser' : null));

        $response->setIsp($body->asn->name);
        $response->setOrganization($body->asn->name);
        $response->setAs($body->asn->asn.' '.$body->asn->name);

        if (isset($body->carrier) && !empty($body->carrier)) {
            $response->setMobile(true)
                ->setIsp($body->carrier->name);
        } else {
            $response->setMobile(false);
        }

        return $response;
    }

    protected function parseBatchResponse(array $body): array
    {
        // Not currently implemented
        return [];
    }
}
