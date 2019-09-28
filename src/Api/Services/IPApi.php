<?php


namespace FoF\GeoIP\Api\Services;

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Api\ServiceInterface;
use FoF\GeoIP\Api\ServiceResponse;
use GuzzleHttp\Client;

class IPApi implements ServiceInterface
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Client
     */
    private $client;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;

        $this->client = new Client([
            'base_uri' => 'http://ip-api.com'
        ]);
    }

    /**
     * @param string $ip
     * @return ServiceResponse|null
     */
    public function get(string $ip)
    {
        $res = $this->client->get(
            "/json/{$ip}",
            ['query' => [
                'fields' => 'status,message,countryCode,isp,org'
            ]]
        );

        $body = json_decode($res->getBody());

        if ($body->status != 'success') return (new ServiceResponse())
            ->setError($body->message);

        $data = (new ServiceResponse())
            ->setCountryCode($body->countryCode)
            ->setZipCode($body->zip)
            ->setIsp($body->isp)
            ->setOrganization($body->org);

        return $data;
    }
}
