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

class IPInfo extends BaseGeoService
{
    protected $host = 'https://ipinfo.io';
    protected $settingPrefix = 'fof-geoip.services.ipinfo';

    protected function requiresApiKey(): bool
    {
        return false;
    }

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        $ip = urlencode($ip);

        return "/{$ip}";
    }

    protected function buildBatchUrl(array $ips, ?string $apiKey): string
    {
        // Not currently implemented
        return '';
    }

    public function isRateLimited(): bool
    {
        return true;
    }

    protected function parseBatchResponse(array $body): array
    {
        // Not currently implemented
        return [];
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        $response = new ServiceResponse($this->host);

        // this provider returns location in the `loc` attribute as comma separated values
        if (isset($body->loc) && !empty($body->loc)) {
            [$latitude, $longitude] = explode(',', $body->loc);
            $response->setLatitude($latitude)
                ->setLongitude($longitude);
        }

        // asn and organisation are provied as a single string
        // We need to split them into separate values
        $asn = null;
        $organization = null;
        $isp = null;

        if (isset($body->org) && !empty($body->org)) {
            [$asn, $organization] = explode(' ', $body->org, 2);
            $isp = $organization;
        }

        $response
            ->setIP($body->ip)
            ->setCountryCode($body->country)
            ->setZipCode($body->postal)
            ->setIsp($isp)
            ->setOrganization($organization)
            ->setAs($asn);

        return $response;
    }

    protected function updateRateLimitsFromResponse(ResponseInterface $response, string $requestType = 'single'): void
    {

    }

    protected function getRequestOptions(?string $apiKey, ?array $ips = null): array
    {
        $options = [];

        $options['http_errors'] = false;

        return $options;
    }

    protected function hasError(ResponseInterface $response, mixed $body): bool
    {
        $code = $response->getStatusCode();

        return $code < 200 || $code >= 300 || isset($body?->error);
    }

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipinfo', $body->error->message ?? json_encode($body));
    }
}
