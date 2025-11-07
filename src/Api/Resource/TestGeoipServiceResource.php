<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Api\GeoIP;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Uri;
use Tobyz\JsonApiServer\Context as OriginalContext;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Exception\ForbiddenException;

/**
 * @extends Resource\AbstractResource<object>
 */
class TestGeoipServiceResource extends Resource\AbstractResource
{
    public function __construct(
        protected GeoIP $geoIP,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function type(): string
    {
        return 'geoip';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Endpoint::make('test')
                ->route('GET', '/test')
                ->authenticated()
                ->can('admin')
                ->action(function (Context $context) {
                    $actor = $context->getActor();
                    if (!$actor->isAdmin()) {
                        throw new ForbiddenException();
                    }

                    $queryParams = $context->request->getQueryParams();
                    $ip = trim(urldecode(Arr::get($queryParams, 'ip', '')));

                    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                        throw new BadRequestException('Invalid IP address provided');
                    }

                    $service = $this->geoIP->getService();
                    $serviceName = $this->geoIP->getServiceName();

                    if (!$service) {
                        throw new \Exception('No GeoIP service configured');
                    }

                    try {
                        $startTime = microtime(true);

                        // Get the raw HTTP response directly from the service
                        $rawHttpResponse = $this->getRawServiceResponse($service, $ip);

                        // Also get the processed response
                        $response = $service->get($ip);
                        $endTime = microtime(true);

                        // Determine if this was actually successful
                        $isSuccess = $this->isResponseSuccessful($rawHttpResponse, $response);
                        $errorMessage = $this->getErrorMessage($rawHttpResponse, $response);

                        return (object) [
                            'id'                 => 'test',
                            'success'            => $isSuccess,
                            'service'            => $serviceName,
                            'ip'                 => $ip,
                            'response_time_ms'   => round(($endTime - $startTime) * 1000, 2),
                            'processed_response' => $response ? $response->toJSON() : null,
                            'service_response'   => $response ? $response->jsonSerialize() : null,
                            'raw_http_response'  => $rawHttpResponse['body'] ?? $rawHttpResponse['error'] ?? null,
                            'response_headers'   => $rawHttpResponse['headers'] ?? [],
                            'http_status_code'   => $rawHttpResponse['status_code'] ?? null,
                            'request_url'        => $rawHttpResponse['url'] ?? null,
                            'request_options'    => $rawHttpResponse['request_options'] ?? null,
                            'error'              => $errorMessage,
                            'timestamp'          => Carbon::now()->toISOString(),
                        ];
                    } catch (\Exception $e) {
                        return (object) [
                            'id'         => 'test',
                            'success'    => false,
                            'service'    => $serviceName,
                            'ip'         => $ip,
                            'error'      => $e->getMessage(),
                            'error_code' => $e->getCode(),
                            'timestamp'  => Carbon::now()->toISOString(),
                        ];
                    }
                }),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Boolean::make('success'),
            Schema\Str::make('service')->nullable(),
            Schema\Str::make('ip')->nullable(),
            Schema\Number::make('response_time_ms')->nullable(),
            Schema\Str::make('processed_response')->nullable(),
            Schema\Arr::make('service_response')->nullable(),
            Schema\Str::make('raw_http_response')->nullable(),
            Schema\Arr::make('response_headers')->nullable(),
            Schema\Number::make('http_status_code')->nullable(),
            Schema\Str::make('request_url')->nullable(),
            Schema\Arr::make('request_options')->nullable(),
            Schema\Str::make('error')->nullable(),
            Schema\Number::make('error_code')->nullable(),
            Schema\Str::make('timestamp')->nullable(),
        ];
    }

    private function isResponseSuccessful(array $rawHttpResponse, $serviceResponse): bool
    {
        // Check HTTP status code first
        $httpStatus = $rawHttpResponse['status_code'] ?? null;
        if ($httpStatus && ($httpStatus < 200 || $httpStatus >= 300)) {
            return false;
        }

        // Check if service response indicates an error
        if ($serviceResponse && $serviceResponse->getError()) {
            return false;
        }

        // Check raw response body for common error indicators
        if (isset($rawHttpResponse['body'])) {
            try {
                $body = json_decode($rawHttpResponse['body'], true);
                if ($body) {
                    if (isset($body['status']) && $body['status'] === 'fail') {
                        return false;
                    }
                    if (isset($body['success']) && $body['success'] === false) {
                        return false;
                    }
                    if (isset($body['error'])) {
                        return false;
                    }
                }
                /** @phpstan-ignore-next-line */
            } catch (\Exception $e) {
                // Ignore JSON parsing errors
            }
        }

        // Check for error in raw response
        if (isset($rawHttpResponse['error'])) {
            return false;
        }

        return true;
    }

    private function getErrorMessage(array $rawHttpResponse, $serviceResponse): ?string
    {
        // First check service response error
        if ($serviceResponse && $serviceResponse->getError()) {
            return $serviceResponse->getError();
        }

        // Check raw response body for error message
        if (isset($rawHttpResponse['body'])) {
            try {
                $body = json_decode($rawHttpResponse['body'], true);
                if ($body) {
                    if (isset($body['message'])) {
                        return $body['message'];
                    }
                    if (isset($body['error'])) {
                        return is_string($body['error']) ? $body['error'] : json_encode($body['error']);
                    }
                }
            } catch (\Exception $e) {
                // Ignore JSON parsing errors
            }
        }

        // Check for error in raw response
        if (isset($rawHttpResponse['error'])) {
            return $rawHttpResponse['error'];
        }

        // Check HTTP status
        $httpStatus = $rawHttpResponse['status_code'] ?? null;
        if ($httpStatus && ($httpStatus < 200 || $httpStatus >= 300)) {
            return "HTTP Error: {$httpStatus}";
        }

        return null;
    }

    private function getRawServiceResponse($service, string $ip): array
    {
        try {
            $apiKey = $this->settings->get("fof-geoip.services.{$this->geoIP->getServiceName()}.access_key");

            if (!empty($apiKey)) {
                $apiKey = trim($apiKey);
            }

            // Use reflection to access protected methods and properties
            $reflection = new \ReflectionClass($service);

            // Get the buildUrl method
            $buildUrlMethod = $reflection->getMethod('buildUrl');
            $buildUrlMethod->setAccessible(true);
            $host = $reflection->getProperty('host')->getValue($service);
            $url = $buildUrlMethod->invoke($service, $ip, $apiKey);

            // Get the request options
            $getRequestOptionsMethod = $reflection->getMethod('getRequestOptions');
            $getRequestOptionsMethod->setAccessible(true);
            $options = $getRequestOptionsMethod->invoke($service, $apiKey);

            $uri = new Uri($host.$url);
            $uri->withQuery(http_build_query($options['query'] ?? []));

            // Get the HTTP client
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $client = $clientProperty->getValue($service);

            // Make the raw HTTP request
            $httpResponse = $client->get($url, $options);

            return [
                'status_code'     => $httpResponse->getStatusCode(),
                'headers'         => $httpResponse->getHeaders(),
                'body'            => $httpResponse->getBody()->getContents(),
                'url'             => $uri->__toString(),
                'request_options' => $options,
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to get raw response: '.$e->getMessage(),
            ];
        }
    }
}
