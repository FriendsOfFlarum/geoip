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

class NominatimControllerTest extends TestCase
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
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.viewIpsPosts'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 4],
            ],
        ]);
    }

    public static function nonAuthorizedUsers(): array
    {
        return [
            'guest'             => [null],
            'user without perm' => [3],
        ];
    }

    // -------------------------------------------------------------------------
    // Permission checks
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('nonAuthorizedUsers')]
    public function unauthorized_users_cannot_access_nominatim_proxy(?int $userId)
    {
        // Add a user without viewIps for the data provider case
        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'noperm', 'email' => 'noperm@example.com', 'is_email_confirmed' => 1],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => $userId])
                ->withQueryParams(['type' => 'reverse', 'lat' => '51.5', 'lon' => '-0.1'])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_access_nominatim_proxy()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'reverse', 'lat' => '51.5', 'lon' => '-0.1'])
        );

        // 200 or 502 (if Nominatim is unreachable in CI) — either way, not a 403/401
        $this->assertNotEquals(403, $response->getStatusCode());
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function user_with_viewips_permission_can_access_nominatim_proxy()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 2])
                ->withQueryParams(['type' => 'reverse', 'lat' => '51.5', 'lon' => '-0.1'])
        );

        $this->assertNotEquals(403, $response->getStatusCode());
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Input validation — type param
    // -------------------------------------------------------------------------

    #[Test]
    public function missing_type_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['lat' => '51.5', 'lon' => '-0.1'])
        );

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    #[Test]
    public function invalid_type_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'forward', 'q' => 'London'])
        );

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertStringContainsString('"reverse" or "search"', $data['errors'][0]['detail']);
    }

    // -------------------------------------------------------------------------
    // Input validation — reverse
    // -------------------------------------------------------------------------

    #[Test]
    public function reverse_with_missing_lat_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'reverse', 'lon' => '-0.1'])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function reverse_with_non_numeric_lat_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'reverse', 'lat' => 'abc', 'lon' => '-0.1'])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function reverse_with_out_of_range_lat_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'reverse', 'lat' => '91', 'lon' => '0'])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function reverse_with_out_of_range_lon_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'reverse', 'lat' => '0', 'lon' => '181'])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Input validation — search
    // -------------------------------------------------------------------------

    #[Test]
    public function search_with_missing_q_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'search'])
        );

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertStringContainsString('q parameter is required', $data['errors'][0]['detail']);
    }

    #[Test]
    public function search_with_oversized_q_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'search', 'q' => str_repeat('a', 201)])
        );

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertStringContainsString('too long', $data['errors'][0]['detail']);
    }

    #[Test]
    public function search_with_invalid_countrycodes_returns_400()
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/nominatim', ['authenticatedAs' => 1])
                ->withQueryParams(['type' => 'search', 'q' => 'London', 'countrycodes' => 'not_valid!'])
        );

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertStringContainsString('ISO 3166-1', $data['errors'][0]['detail']);
    }
}
