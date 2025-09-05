<?php

namespace FoF\GeoIP\Api\Services;

use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\ServiceResponse;
use Psr\Http\Message\ResponseInterface;

class IPInfoLite extends IPInfo
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

    protected function getRequestOptions(?string $apiKey, ?array $ips = null): array
    {
        $options = parent::getRequestOptions($apiKey, $ips);
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

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipinfo-lite', $body->error->message ?? json_encode($body));
    }
}
