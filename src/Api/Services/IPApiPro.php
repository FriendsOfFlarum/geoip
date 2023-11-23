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

class IPApiPro extends IPApi
{
    protected $host = 'https://ip-api.com';
    protected $settingPrefix = 'fof-geoip.services.ipapi-pro';

    protected function requiresApiKey(): bool
    {
        return true;
    }

    public function isRateLimited(): bool
    {
        return false;
    }
}
