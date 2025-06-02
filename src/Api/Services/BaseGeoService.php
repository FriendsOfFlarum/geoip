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

use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Api\ServiceResponse;
use FoF\GeoIP\Concerns\ServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as Cache;
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
    protected $requestFields;

    protected int $singleLookupsRemaining = 1;
    protected int $batchLookupsRemaining = 1;

    public function __construct(protected SettingsRepositoryInterface $settings, protected LoggerInterface $logger, protected Cache $cache, protected UrlGenerator $url)
    {
        $this->client = new Client([
            'base_uri' => $this->host,
            'verify'   => false,
            'headers'  => [
                'Accept'     => 'application/json',
                'Origin'     => $this->url->to('forum')->base(),
            ],
        ]);
    }

    public function get(string $ip): ?ServiceResponse
    {
        $apiKey = $this->settings->get("{$this->settingPrefix}.access_key");

        if (!empty($apiKey)) {
            // ensure that we don't have any whitespace, etc in the key
            $apiKey = trim($apiKey);
        }

        if ($this->requiresApiKey() && !$apiKey) {
            $this->logger->error("No API key found for {$this->host}");

            return null;
        }

        if ($this->isRateLimited()) {
            $this->initRateLimitsFromCache();
        }

        /** @phpstan-ignore-next-line */
        if (!$this->isRateLimited() || ($this->isRateLimited() && $this->singleLookupsRemaining > 0)) {
            $url = $this->buildUrl($ip, $apiKey);
            $options = $this->getRequestOptions($apiKey);
            $response = $this->client->get($url, $options);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error("Error detected in response from {$this->host}", ['status' => $response->getStatusCode(), 'body' => $response->getBody()->getContents(), 'requestUrl' => $url, 'requestOptions' => $options]);

                return null;
            }

            if ($this->isRateLimited()) {
                $this->updateRateLimitsFromResponse($response);
            }

            $body = json_decode($response->getBody()->getContents());

            if ($this->hasError($response, $body)) {
                $this->logger->error("Error detected in response from {$this->host}");

                return $this->handleError($response, $body);
            }

            return $this->parseResponse($body);
        } else {
            $this->storeForLaterLookup($ip);

            return null;
        }
    }

    protected function requiresApiKey(): bool
    {
        return true;
    }

    public function batchSupported(): bool
    {
        return false;
    }

    public function getBatch(array $ips)
    {
        $apiKey = $this->settings->get("{$this->settingPrefix}.access_key");

        if ($this->requiresApiKey() && !$apiKey) {
            $this->logger->error("No API key found for {$this->host}");

            return null;
        }

        if ($this->isRateLimited()) {
            $this->initRateLimitsFromCache();
        }

        /** @phpstan-ignore-next-line */
        if (!$this->isRateLimited() || ($this->isRateLimited() && $this->batchLookupsRemaining > 0)) {
            $url = $this->buildBatchUrl($ips, $apiKey);
            $options = $this->getRequestOptions($apiKey, $ips);
            $response = $this->client->post($url, $this->getRequestOptions($apiKey, $ips));

            if ($response->getStatusCode() !== 200) {
                $this->logger->error("Error detected in response from {$this->host}", ['status' => $response->getStatusCode(), 'body' => $response->getBody()->getContents(), 'requestUrl' => $url, 'requestOptions' => $options]);
            }

            if ($this->isRateLimited()) {
                $this->updateRateLimitsFromResponse($response, 'batch');
            }

            return $this->parseBatchResponse(json_decode($response->getBody()->getContents()));
        } else {
            $this->storeForLaterLookup($ips);
        }
    }

    protected function storeForLaterLookup(string|array $ip): void
    {
        // TODO: implement this, and a way to process stored IPs when the rate limit is reset.

        //dd($ip, 'Rate limit reached');
    }

    protected function initRateLimitsFromCache(): void
    {
        $this->singleLookupsRemaining = $this->cache->get("{$this->settingPrefix}.single", $this->singleLookupsRemaining);
        $this->batchLookupsRemaining = $this->cache->get("{$this->settingPrefix}.batch", $this->batchLookupsRemaining);
    }

    abstract public function isRateLimited(): bool;

    abstract protected function updateRateLimitsFromResponse(ResponseInterface $response, string $requestType = 'single'): void;

    abstract protected function buildUrl(string $ip, ?string $apiKey): string;

    abstract protected function buildBatchUrl(array $ips, ?string $apiKey): string;

    abstract protected function getRequestOptions(?string $apiKey, ?array $ips = null): array;

    abstract protected function hasError(ResponseInterface $response, mixed $body): bool;

    abstract protected function handleError(ResponseInterface $response, object $body): ?ServiceResponse;

    abstract protected function parseResponse(object $body): ServiceResponse;

    abstract protected function parseBatchResponse(array $body): array;
}
