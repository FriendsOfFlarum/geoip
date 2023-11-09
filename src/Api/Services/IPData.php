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
                'fields'  => 'country_code,postal,asn,threat',
                'api-key' => $apiKey,
            ],
        ];
    }

    protected function hasError(object $body): bool
    {
        return isset($body->error);
    }

    protected function handleError(object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipdata', $body->error->message ?? json_encode($body));
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        $response = (new ServiceResponse())
            ->setCountryCode($body->country_code)
            ->setZipCode($body->postal)
            ->setThreatLevel($body->threat->is_threat)
            ->setThreatType($body->threat->is_known_attacker ? 'attacker' : ($body->threat->is_known_abuser ? 'abuser' : null));

        if (isset($body->asn->type)) {
            if ($body->asn->type == 'isp') {
                /** @phpstan-ignore-next-line */
                $response->setIsp($body->asn->name);
            } else {
                /** @phpstan-ignore-next-line */
                $response->setOrganization($body->asn->name);
            }
        }

        return $response;
    }
}
