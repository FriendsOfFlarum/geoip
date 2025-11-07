<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\integration\Api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class TestGeoipControllerTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    public static function nonAdminUsers(): array
    {
        return [
            'guest' => [null],
            'normal user' => [2],
        ];
    }

    #[Test]
    #[DataProvider('nonAdminUsers')]
    public function non_admin_users_cannot_access_test_endpoint(?int $userId)
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/test', [
                'authenticatedAs' => $userId,
            ])
            ->withQueryParams(['ip' => '8.8.8.8'])
        );

        // Non-admin users get 403 because assertAdmin() throws PermissionDeniedException
        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_access_test_endpoint_with_valid_ip()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/test', [
                'authenticatedAs' => 1,
            ])
            ->withQueryParams(['ip' => '8.8.8.8'])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);

        $this->assertEquals('geoip-test', $data['data']['type']);
        $this->assertEquals('test', $data['data']['id']);
        $this->assertArrayHasKey('attributes', $data['data']);

        $attributes = $data['data']['attributes'];
        $this->assertArrayHasKey('success', $attributes);
        $this->assertArrayHasKey('service', $attributes);
        $this->assertArrayHasKey('ip', $attributes);
        $this->assertEquals('8.8.8.8', $attributes['ip']);
        $this->assertArrayHasKey('timestamp', $attributes);
    }

    #[Test]
    public function admin_gets_error_with_invalid_ip()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/test', [
                'authenticatedAs' => 1,
            ])
            ->withQueryParams(['ip' => 'not-an-ip'])
        );

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('400', $data['errors'][0]['status']);
        $this->assertStringContainsString('Invalid IP address', $data['errors'][0]['detail']);
    }

    #[Test]
    public function admin_gets_error_with_missing_ip()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/test', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertEquals('400', $data['errors'][0]['status']);
    }

    #[Test]
    public function response_includes_expected_fields_on_success()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/test', [
                'authenticatedAs' => 1,
            ])
            ->withQueryParams(['ip' => '1.1.1.1'])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);

        $attributes = $data['data']['attributes'];

        // Check all expected fields are present
        $expectedFields = [
            'success',
            'service',
            'ip',
            'response_time_ms',
            'processed_response',
            'service_response',
            'raw_http_response',
            'response_headers',
            'http_status_code',
            'request_url',
            'request_options',
            'timestamp',
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $attributes, "Missing field: {$field}");
        }

        // Verify types
        $this->assertIsBool($attributes['success']);
        $this->assertIsString($attributes['service']);
        $this->assertIsString($attributes['ip']);
        $this->assertIsString($attributes['timestamp']);

        // If response_time_ms is not null, it should be numeric
        if ($attributes['response_time_ms'] !== null) {
            $this->assertIsNumeric($attributes['response_time_ms']);
        }

        // If http_status_code is not null, it should be numeric
        if ($attributes['http_status_code'] !== null) {
            $this->assertIsNumeric($attributes['http_status_code']);
        }
    }
}
