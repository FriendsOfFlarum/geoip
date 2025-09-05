<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Concerns\ServiceInterface;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Traits\HandlesGeoIPErrors;

class GeoIP
{
    use HandlesGeoIPErrors;

    public const FREE_SERVICES = [
        'ipapi'      => Services\IPApi::class,
        'iplocation' => Services\IPLocation::class,
    ];

    public const PRO_SERVICES = [
        'ipapi-pro'  => Services\IPApiPro::class,
        'ipdata'     => Services\IPData::class,
        'ipsevenex'  => Services\IPSevenEx::class,
    ];

    public const SERVICES = self::FREE_SERVICES + self::PRO_SERVICES;

    public static array $freeServices = self::FREE_SERVICES;

    public static array $proServices = self::PRO_SERVICES;

    public static array $services = self::SERVICES;

    private $prefix = 'fof-geoip.services';

    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function getService(): ?ServiceInterface
    {
        $serviceName = $this->settings->get('fof-geoip.service');
        $service = self::$services[$serviceName] ?? null;

        if (!$service) {
            return null;
        }

        return resolve($service);
    }

    public function getServiceName(): ?string
    {
        return $this->settings->get('fof-geoip.service');
    }

    public function batchSupported(): bool
    {
        $service = $this->getService();

        return $service && $service->batchSupported();
    }

    /**
     * @param string $ip
     *
     * @return ServiceResponse|null
     */
    public function get(string $ip): ?ServiceResponse
    {
        return $this->getService()->get($ip);
    }

    /**
     * @param array $ips
     *
     * @return ServiceResponse[]
     */
    public function getBatch(array $ips)
    {
        return $this->getService()->getBatch($ips);
    }

    public function getSaved(string $ip): ?IPInfo
    {
        return IPInfo::where('address', $ip)->first();
    }

    protected function checkErrors(): ?ServiceResponse
    {
        $serviceName = $this->settings->get('fof-geoip.service');
        $service = self::$services[$serviceName] ?? null;

        if (!$service) {
            return null;
        }

        $timeKey = "{$this->prefix}.$serviceName.last_error_time";
        $errorKey = "{$this->prefix}.$serviceName.error";
        $lastErrorTime = $this->settings->get($timeKey);

        if ($lastErrorTime && Carbon::createFromTimestamp($lastErrorTime)->isAfter(Carbon::now()->subHour())) {
            return $this->handleGeoIPError($service, $this->settings->get($errorKey));
        } elseif ($lastErrorTime) {
            $this->settings->delete($timeKey);
            $this->settings->delete($errorKey);
        }

        return null;
    }

    public static function setError(string $service, string $error)
    {
        $settings = resolve('flarum.settings');

        $settings->set("fof-geoip.services.$service.last_error_time", time());
        $settings->set("fof-geoip.services.$service.error", $error);

        return self::getFakeResponse($service, $error);
    }

    protected static function getFakeResponse(string $service, string $error)
    {
        return (new ServiceResponse($service, true))
            ->setError($error);
    }
}
