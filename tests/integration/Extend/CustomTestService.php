<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\integration\Extend;

use FoF\GeoIP\Api\ServiceResponse;
use FoF\GeoIP\Concerns\ServiceInterface;

/**
 * A minimal third-party service, standing in for one an unrelated extension
 * might register through the Services extender.
 */
class CustomTestService implements ServiceInterface
{
    public function get(string $ip): ?ServiceResponse
    {
        return (new ServiceResponse('custom'))->setIP($ip)->setCountryCode('ZZ');
    }

    public function getBatch(array $ips): array
    {
        return array_values(array_filter(array_map([$this, 'get'], $ips)));
    }

    public function batchSupported(): bool
    {
        return false;
    }

    public function settings(): array
    {
        return [];
    }
}
