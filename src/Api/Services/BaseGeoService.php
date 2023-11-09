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
use FoF\GeoIP\Api\ServiceResponse;
use FoF\GeoIP\Concerns\ServiceInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseGeoService implements ServiceInterface
{
    /**
     * @var Client
     */
    protected $client;

    protected $host;
    protected $settingPrefix;

    public function __construct(protected SettingsRepositoryInterface $settings, protected LoggerInterface $logger)
    {
        $this->client = new Client([
            'base_uri' => $this->host,
            'verify'   => false,
        ]);
    }

    public function get(string $ip): ?ServiceResponse
    {
        $apiKey = $this->settings->get("{$this->settingPrefix}.access_key");

        if ($this->requiresApiKey() && !$apiKey) {
            $this->logger->error("No API key found for {$this->host}");

            return null;
        }

        $res = $this->client->get($this->buildUrl($ip, $apiKey), $this->getRequestOptions($apiKey));

        $body = json_decode($res->getBody());

        if ($this->hasError($res, $body)) {
            $this->logger->error("Error detected in response from {$this->host}");

            return $this->handleError($res, $body);
        }

        $data = $this->parseResponse($body);

        return $data;
    }

    protected function requiresApiKey(): bool
    {
        return true;
    }

    abstract protected function buildUrl(string $ip, ?string $apiKey): string;

    abstract protected function getRequestOptions(?string $apiKey): array;

    abstract protected function hasError(ResponseInterface $response, object $body): bool;

    abstract protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse;

    abstract protected function parseResponse(object $body): ServiceResponse;
}
