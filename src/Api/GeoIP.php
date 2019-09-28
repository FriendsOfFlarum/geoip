<?php


namespace FoF\GeoIP\Api;


use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Api\Services;

class GeoIP
{
    public static $services = [
        'freegeoip' => Services\FreeGeoIP::class,
        'ipapi' => Services\IPApi::class,
        'ipdata' => Services\IPData::class,
        'ipstack' => Services\IPStack::class,
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
     * @return ServiceResponse|void
     */
    public function get(string $ip) {
        $serviceName = $this->settings->get('fof-geoip.service');
        $service = self::$services[$serviceName] ?? null;

        if ($service == null) return;

        return app()->make($service)->get($ip);
    }
}
