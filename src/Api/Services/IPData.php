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
use GuzzleHttp\Exception\RequestException;

class IPData implements ServiceInterface
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
            'base_uri' => 'https://api.ipdata.co',
            'verify'   => false,
        ]);
    }

    /**
     * @param string $ip
     *
     * @return ServiceResponse|null
     */
    public function get(string $ip)
    {
        $apiKey = $this->settings->get('fof-geoip.services.ipdata.access_key');

        if (!$apiKey) {
            return;
        }

        $res = null;

        try {
            $res = $this->client->get("/{$ip}", [
                'query' => [
                    'fields'  => 'country_code,postal,asn,threat',
                    'api-key' => $apiKey,
                ],
            ]);
        } catch (RequestException $e) {
            $body = json_decode($e->getResponse()->getBody());

            return (new ServiceResponse())
                ->setError($body->message);
        }

        $body = json_decode($res->getBody());

        $data = (new ServiceResponse())
            ->setCountryCode($body->country_code)
            ->setZipCode($body->postal)
            ->setThreatLevel($body->threat->is_threat)
            ->setThreatType($body->threat->is_known_attacker ? 'attacker' : ($body->threat->is_known_abuser ? 'abuser' : null));

        if ($body->asn->type == 'isp') {
            $data->setIsp($body->asn->name);
        } else {
            $data->setOrganization($body->asn->name);
        }

        return $data;
    }
}
