<?php

namespace FoF\GeoIP\Api\Services;

use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\ServiceResponse;

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
            'delay' => 100,
            'retries' => 3,
            'query' => [
                'fields' => 'status,message,countryCode,zip,isp,org',
            ]
        ];
    }

    protected function hasError(object $body): bool
    {
        return $body->status !== 'success';
    }

    protected function handleError(object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipapi', $body->message ?? json_encode($body));
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        return (new ServiceResponse())
            ->setCountryCode($body->countryCode)
            ->setZipCode($body->zip)
            ->setIsp($body->isp)
            ->setOrganization($body->org);
    }
}
