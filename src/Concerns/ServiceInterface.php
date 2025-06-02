<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Concerns;

use FoF\GeoIP\Api\ServiceResponse;

interface ServiceInterface
{
    public function get(string $ip): ?ServiceResponse;

    public function getBatch(array $ips);

    public function batchSupported(): bool;
}
