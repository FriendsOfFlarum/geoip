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
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class IPData extends BaseGeoService
{
    protected $host = 'https://api.ipdata.co';
    protected $settingPrefix = 'fof-geoip.services.ipdata';

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        return "/{$ip}";
    }

    protected function getRequestOptions(?string $apiKey): array
    {
        return [
            'http_errors' => false,
            'delay'       => 100,
            'retries'     => 3,
            'query'       => [
                'fields'  => 'country_code,postal,asn,threat,carrier,latitude,longitude',
                'api-key' => $apiKey,
            ],
        ];
    }

    protected function hasError(ResponseInterface $response, object $body): bool
    {
        return isset($body->error) || ($response->getStatusCode() >= 400 && !Str::contains($body->message, ['is a reserved IP address']));
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
        $response->setAs($body->asn->asn . ' ' . $body->asn->name);

        if (isset($body->carrier) && !empty($body->carrier)) {
            $response->setMobile(true)
                ->setIsp($body->carrier->name);
        } else {
            $response->setMobile(false);
        }

        return $response;
    }
}
