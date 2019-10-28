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

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;

class GeoIP
{
    public static $services = [
        'freegeoip' => Services\FreeGeoIP::class,
        'ipapi'     => Services\IPApi::class,
        'ipdata'    => Services\IPData::class,
        'ipstack'   => Services\IPStack::class,
    ];

    private $prefix = 'fof-geoip.services';

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

        $timeKey = "{$this->prefix}.$serviceName.last_error_time";
        $errorKey = "{$this->prefix}.$serviceName.error";
        $lastErrorTime = $this->settings->get($timeKey);

        if ($lastErrorTime && Carbon::createFromTimestamp($lastErrorTime)->isAfter(Carbon::now()->subHour())) {
            return self::getFakeResponse($this->settings->get($errorKey));
        } else if ($lastErrorTime) {
            $this->settings->delete($timeKey);
            $this->settings->delete($errorKey);
        }

        return app()->make($service)->get($ip);
    }

    public static function setError(string $service, string $error) {
        $settings = app('flarum.settings');

        $settings->set("fof-geoip.services.$service.last_error_time", time());
        $settings->set("fof-geoip.services.$service.error", $error);

        return self::getFakeResponse($error);
    }

    protected static function getFakeResponse(string $error) {
        return (new ServiceResponse(true))
            ->setError($error);
    }
}
