<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\Serializer\TestGeoipServiceSerializer;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class TestGeoipServiceController extends AbstractShowController
{
    public $serializer = TestGeoipServiceSerializer::class;

    public function __construct(protected GeoIP $geoIP, protected SettingsRepositoryInterface $settings)
    {
    }

    public function data(ServerRequestInterface $request, Document $document): array
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $ip = urldecode(Arr::get($request->getQueryParams(), 'ip'));

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address provided');
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

            // Also get the processed response (but don't time this since we already made the request above)
            $response = $service->get($ip);
            $endTime = microtime(true);

            // Determine if this was actually successful
            $isSuccess = $this->isResponseSuccessful($rawHttpResponse, $response);
            $errorMessage = $this->getErrorMessage($rawHttpResponse, $response);

            return [
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
            return [
                'success'    => false,
                'service'    => $serviceName,
                'ip'         => $ip,
                'error'      => $e->getMessage(),
                'error_code' => $e->getCode(),
                'timestamp'  => Carbon::now()->toISOString(),
            ];
        }
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
                    // Check for common error status fields
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
                // If we can't parse the JSON, we can't determine error status from body
                // We purposely ignore this error in PHPStan
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
                // If we can't parse JSON, fall through to other checks
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
            $url = $buildUrlMethod->invoke($service, $ip, $apiKey);

            // Get the request options
            $getRequestOptionsMethod = $reflection->getMethod('getRequestOptions');
            $getRequestOptionsMethod->setAccessible(true);
            $options = $getRequestOptionsMethod->invoke($service, $apiKey);

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
                'url'             => $url,
                'request_options' => $options,
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to get raw response: '.$e->getMessage(),
            ];
        }
    }
}
