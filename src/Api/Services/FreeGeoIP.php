<?php


namespace FoF\GeoIP\Api\Services;


use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Api\ServiceInterface;
use FoF\GeoIP\Api\ServiceResponse;
use GuzzleHttp\Client;

class FreeGeoIP implements ServiceInterface
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://freegeoip.app',
            'verify' => false
        ]);
    }

    /**
     * @param string $ip
     * @return ServiceResponse|null
     */
    public function get(string $ip)
    {
        $res = $this->client->get("/json/{$ip}");
        $body = json_decode($res->getBody());

        return (new ServiceResponse())
            ->setCountryCode($body->country_code)
            ->setZipCode($body->zip_code);
    }
}
