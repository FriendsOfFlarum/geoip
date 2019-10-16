<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Repositories;

use Flarum\Post\Post;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\IPInfo;
use Illuminate\Cache\Repository;

class GeoIPRepository
{
    /**
     * @var GeoIP
     */
    protected $geoip;

    /**
     * @var Repository
     */
    protected $cache;

    public function __construct(GeoIP $geoip, Repository $cache)
    {
        $this->geoip = $geoip;
        $this->cache = $cache;
    }

    /**
     * @param string|null $ip
     *
     * @return IPInfo
     */
    public function get($ip)
    {
        if (!$ip) {
            return;
        }

        return IPInfo::where('address', $ip)->first() ?? $this->obtain($ip);
    }

    private function obtain(?string $ip)
    {
        $response = $this->geoip->get($ip);

        if ($response) {
            $data = new IPInfo();

            $data->address = $ip;
            $data->fill($response->toJson());
            $data->save();
        }

        return $data ?? null;
    }
}
