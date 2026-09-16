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
use FoF\GeoIP\Api\GeoIP;
use PHPUnit\Framework\Attributes\Test;

/**
 * Each service declares the settings it needs in PHP, and the admin page
 * renders whatever is declared.
 *
 * Previously the frontend carried a hardcoded list of which services take an
 * API key, duplicating `requiresApiKey()` in PHP. The two could drift — and
 * did: two keyed services were missing from the list — and a new service could
 * not be configured at all without editing the frontend.
 */
class ServiceSettingsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        GeoIP::$forced = null;
        GeoIP::$configured = [];
    }

    private function settingsFor(string $service): array
    {
        $this->setting('fof-geoip.service', $service);

        return $this->app()->getContainer()->make(GeoIP::class)->serviceSettings();
    }

    #[Test]
    public function a_keyed_service_declares_an_access_key_field(): void
    {
        $settings = $this->settingsFor('ipapi-pro');

        $this->assertArrayHasKey('fof-geoip.services.ipapi-pro.access_key', $settings);
        $this->assertSame('text', $settings['fof-geoip.services.ipapi-pro.access_key']['type']);
        $this->assertTrue($settings['fof-geoip.services.ipapi-pro.access_key']['required']);
    }

    #[Test]
    public function a_keyless_service_declares_no_access_key(): void
    {
        $settings = $this->settingsFor('ipapi');

        $this->assertArrayNotHasKey('fof-geoip.services.ipapi.access_key', $settings);
    }

    /**
     * ipdata and ipsevenex both require a key but were absent from the
     * frontend's hardcoded list. Declaring it in PHP removes the possibility.
     */
    /**
     * ipdata and ipsevenex both require a key but were absent from the
     * frontend's old hardcoded list. Deriving the field from requiresApiKey()
     * removes the possibility of that drift.
     *
     * Each service is resolved directly rather than through a settings change,
     * because the container is built once per test and would not pick up a
     * second change within one test method.
     */
    #[Test]
    public function every_service_requiring_a_key_declares_one(): void
    {
        $container = $this->app()->getContainer();

        foreach (['ipdata', 'ipsevenex', 'ipinfo-lite', 'ipapi-pro'] as $service) {
            /** @var \FoF\GeoIP\Concerns\ServiceInterface $instance */
            $instance = $container->make(GeoIP::$services[$service]);

            $this->assertArrayHasKey(
                "fof-geoip.services.$service.access_key",
                $instance->settings(),
                "$service requires an API key but does not declare the setting"
            );
        }
    }

    #[Test]
    public function the_offline_driver_declares_a_path_per_database(): void
    {
        $settings = $this->settingsFor('maxmind');

        foreach (['country', 'city', 'asn'] as $kind) {
            $key = "fof-geoip.services.maxmind.{$kind}_path";

            $this->assertArrayHasKey($key, $settings);
            $this->assertSame('text', $settings[$key]['type']);
            // All three are optional: a country database alone is a valid install.
            $this->assertFalse($settings[$key]['required']);
            $this->assertNotEmpty($settings[$key]['placeholder']);
        }
    }

    #[Test]
    public function the_offline_driver_declares_no_access_key(): void
    {
        $this->assertArrayNotHasKey('fof-geoip.services.maxmind.access_key', $this->settingsFor('maxmind'));
    }

    #[Test]
    public function declared_settings_reach_the_admin_payload(): void
    {
        $this->setting('fof-geoip.service', 'maxmind');

        $response = $this->send(
            $this->request('GET', '/admin', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();

        // The admin frontend renders from this payload, so the keys must be
        // present without any frontend change.
        $this->assertStringContainsString('fof-geoip.serviceSettings', $body);
        $this->assertStringContainsString('country_path', $body);
    }
}
