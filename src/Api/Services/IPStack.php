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

class IPStack extends BaseGeoService
{
    protected $host = 'https://api.ipstack.com';
    protected $settingPrefix = 'fof-geoip.services.ipstack';

    protected function buildUrl(string $ip, ?string $apiKey): string
    {
        return "/api/{$ip}";
    }

    protected function getRequestOptions(?string $apiKey): array
    {
        return [
            'query' => [
                'access_key' => $apiKey,
                'security' => (int) $this->settings->get("{$this->settingPrefix}.security", 0),
            ],
            'http_errors' => false,
            'delay' => 100,
            'retries' => 3,
        ];
    }


    protected function hasError(object $body): bool
    {
        return isset($body->success) && !$body->success;
    }

    protected function handleError(object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipstack', $body->error->info ?? json_encode($body));
    }

    protected function parseResponse(object $body): ServiceResponse
    {
        $data = (new ServiceResponse())
            ->setCountryCode($body->country_code)
            ->setZipCode($body->zip);

        if ($body->connection) {
            $data->setIsp($body->connection->isp);
        }

        if ($body->security) {
            $data
                ->setThreatLevel($body->security->threat_level)
                ->setThreatType(implode(', ', $body->security->threat_types));
        }

        return $data;
    }
}
