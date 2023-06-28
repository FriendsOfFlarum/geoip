<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Services;

use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Api\ServiceInterface;
use FoF\GeoIP\Api\ServiceResponse;
use GuzzleHttp\Client;

class IPLocation implements ServiceInterface
{
    /**
     * @var Client
     */
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.iplocation.net',
        ]);
    }

    /**
     * @param string $ip
     *
     * @return ServiceResponse|null
     */
    public function get(string $ip)
    {
        $res = $this->client->get(
            "/",
            ['query' => [
                'ip' => $ip,
            ]]
        );

        $body = json_decode($res->getBody());

        if ($body->response_code != '200') {
            return (new ServiceResponse())
                ->setError(sprintf("%s %s", $body->response_code, $body->response_message));
        }

        return (new ServiceResponse())
            ->setCountryCode($body->country_code2)
            ->setIsp($body->isp);
    }
}
