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

    protected $retrieving = [];

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
        if (!$ip || in_array($ip, $this->retrieving)) {
            return;
        }

        return IPInfo::where('address', $ip)->first() ?? $this->obtain($ip);
    }

    private function obtain(?string $ip)
    {
        $this->retrieving[] = $ip;

        $response = $this->geoip->get($ip);

        if ($response) {
            $data = new IPInfo();

            $data->address = $ip;
            $data->fill($response->toJson());

            if (!$response->fake && !IPInfo::query()->where('address', $ip)->exists()) {
                $data->save();
            }
        }

        $this->retrieving = array_diff($this->retrieving, [$ip]);

        return $data ?? null;
    }
}
