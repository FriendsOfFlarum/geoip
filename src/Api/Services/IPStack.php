<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Services;

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Api\ServiceInterface;
use FoF\GeoIP\Api\ServiceResponse;
use GuzzleHttp\Client;

class IPStack implements ServiceInterface
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Client
     */
    private $client;

    protected $host = 'https://api.ipstack.com';
    protected $settingPrefix = 'fof-geoip.services.ipstack';

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;

        $this->client = new Client([
            'base_uri' => $this->host,
        ]);
    }

    /**
     * @param string $ip
     *
     * @return ServiceResponse|null
     */
    public function get(string $ip) : ?ServiceResponse
    {
        $accessKey = $this->settings->get("{$this->settingPrefix}.access_key");

        if (!$accessKey) {
            return null;
        }

        $res = $this->client->get(
            "/api/{$ip}",
            ['query' => [
                'access_key' => $accessKey,
                'security'   => (int) $this->settings->get("{$this->settingPrefix}.security", 0),
            ]]
        );

        $body = json_encode($res->getBody());

        $data = (new ServiceResponse())
            ->setCountryCode($body->country_code)
            ->setZipCode($body->zip);

        if ($body->connection) {
            $data->setIsp($body->connection->isp);
        }

        if ($body->security) {
            $data
                ->setThreatLevel($body->security->threat_level)
                ->setThreatTypes($body->security->threat_types);
        }

        return $data;
    }
}
