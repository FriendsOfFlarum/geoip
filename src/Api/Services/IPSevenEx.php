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

class IPSevenEx extends BaseGeoService
{
    protected $host = 'https://api.7x.ax';
    protected $settingPrefix = 'fof-geoip.services.ipsevenex';

    public function isRateLimited(): bool
    {
        return true;
    }

    protected function updateRateLimitsFromResponse(ResponseInterface $response, string $requestType = 'single'): void
    {
    }

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        return "geolocate/v1/ip/$ip";
    }

    protected function buildBatchUrl(array $ips, ?string $apiKey): string
    {
        // Not supported by API
        return '';
    }

    protected function getRequestOptions(?string $apiKey, ?array $ips = null): array
    {
        return [
            'http_errors' => false,
            'query'       => [
                'apikey' => $apiKey,
            ],
        ];
    }

    protected function hasError(ResponseInterface $response, mixed $body): bool
    {
        return $response->getStatusCode() > 200;
    }

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipsevenex', $body->data ?? json_encode($body));
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        $response = new ServiceResponse($this->host);

        $body = $body->data;

        $response->setCountryCode($body->country->iso_code)
            ->setZipCode($body->postal->code)
            ->setLatitude($body->location->latitude)
            ->setLongitude($body->location->longitude)
            ->setIsp($body->traits->isp)
            ->setOrganization($body->traits->autonomous_system_organization);

        return $response;
    }

    protected function parseBatchResponse(array $body): array
    {
        // Not supported by API
        return [];
    }
}
