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

class IPApi extends BaseGeoService
{
    protected $host = 'http://ip-api.com';
    protected $settingPrefix = 'fof-geoip.services.ipapi';

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        return "/json/{$ip}";
    }

    protected function requiresApiKey(): bool
    {
        return false;
    }

    protected function getRequestOptions(?string $apiKey): array
    {
        return [
            'http_errors' => false,
            'delay'       => 100,
            'retries'     => 3,
            'query'       => [
                'fields' => 'status,message,countryCode,city,zip,lat,lon,isp,org,as,mobile',
            ],
        ];
    }

    protected function hasError(ResponseInterface $response, object $body): bool
    {
        $allowedFailures = ['reserved range', 'private range'];

        return $body->status === 'fail' && !in_array($body->message, $allowedFailures);
    }

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipapi', $body->message ?? json_encode($body));
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        $response = new ServiceResponse($this->host);

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
}
