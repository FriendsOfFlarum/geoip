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

class IPLocation extends BaseGeoService
{
    protected $host = 'https://api.iplocation.net';
    protected $settingPrefix = 'fof-geoip.services.iplocation';

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        return "/?ip={$ip}";
    }

    protected function getRequestOptions(?string $apiKey): array
    {
        return [
            'http_errors' => false,
            'delay'       => 100,
            'retries'     => 3,
        ];
    }

    protected function requiresApiKey(): bool
    {
        return false;
    }

    protected function hasError(ResponseInterface $response, object $body): bool
    {
        return $body->response_code !== '200';
    }

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('iplocation', sprintf('%s %s', $body->response_code, $body->response_message));
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        return (new ServiceResponse($this->host))
            ->setCountryCode($body->country_code2)
            ->setIsp($body->isp);
    }
}
