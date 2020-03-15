<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api;

interface ServiceInterface
{
    public function get(string $ip);
}
