<?php


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

    public function __construct(GeoIP $geoip, Repository $cache)
    {
        $this->geoip = $geoip;
        $this->cache = $cache;
    }

    /**
     * @param string|null $ip
     * @return IPInfo
     */
    public function get($ip)
    {
        if (!$ip) return;

        $data = IPInfo::where('address', $ip)->first();

        if (!$data) {
            $response = $this->geoip->get($ip);

            if ($response) {
                $data = new IPInfo();

                $data->address = $ip;
                $data->fill($response->toJson());
                $data->save();
            }
        }

        return $data;
    }
}
