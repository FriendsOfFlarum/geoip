<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api;

use Flarum\Settings\SettingsRepositoryInterface;

class GeoIP
{
    public static $services = [
        'freegeoip' => Services\FreeGeoIP::class,
        'ipapi'     => Services\IPApi::class,
        'ipdata'    => Services\IPData::class,
        'ipstack'   => Services\IPStack::class,
    ];

    /**
     * @var SettingsRepositoryInterface
     */
    private $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param string $ip
     *
     * @return ServiceResponse|void
     */
    public function get(string $ip)
    {
        $serviceName = $this->settings->get('fof-geoip.service');
        $service = self::$services[$serviceName] ?? null;

        if ($service == null) {
            return;
        }

        return app()->make($service)->get($ip);
    }
}
