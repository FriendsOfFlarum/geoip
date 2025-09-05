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

use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\ServiceResponse;
use Psr\Http\Message\ResponseInterface;

class IPInfoLite extends BaseGeoService
{
    protected $host = 'https://api.ipinfo.io';
    protected $settingPrefix = 'fof-geoip.services.ipinfo-lite';

    protected function requiresApiKey(): bool
    {
        return true;
    }

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        $ip = urlencode($ip);

        return "/lite/{$ip}";
    }

    public function isRateLimited(): bool
    {
        return false;
    }

    protected function buildBatchUrl(array $ips, ?string $apiKey): string
    {
        // Not supported by this provider
        return '';
    }

    protected function getRequestOptions(?string $apiKey, ?array $ips = null): array
    {
        $options = [];

        $options['http_errors'] = false;
        $options['query']['token'] = $apiKey;

        return $options;
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        $response = new ServiceResponse($this->host);

        $response
            ->setIP($body->ip)
            ->setCountryCode($body->country_code)
            ->setOrganization($body->as_name)
            ->setIsp($body->as_domain)
            ->setAs($body->asn);

        return $response;
    }

    protected function parseBatchResponse(array $body): array
    {
        // Not supported by this provider
        return [];
    }

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipinfo-lite', $body->error->message ?? json_encode($body));
    }

    protected function hasError(ResponseInterface $response, mixed $body): bool
    {
        $code = $response->getStatusCode();

        return $code < 200 || $code >= 300 || isset($body?->error);
    }

    protected function updateRateLimitsFromResponse(ResponseInterface $response, string $requestType = 'single'): void
    {
    }
}
