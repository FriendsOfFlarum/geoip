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

class IPApiPro extends IPApi
{
    protected $host = 'https://pro.ip-api.com';
    protected $settingPrefix = 'fof-geoip.services.ipapi-pro';

    protected function requiresApiKey(): bool
    {
        return true;
    }

    public function isRateLimited(): bool
    {
        return false;
    }

    protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse
    {
        return GeoIP::setError('ipapi-pro', $body->message ?? json_encode($body));
    }

    protected function getRequestOptions(?string $apiKey, ?array $ips = null): array
    {
        $options = parent::getRequestOptions($apiKey, $ips);
        $options['query']['key'] = $apiKey;

        return $options;
    }
}
