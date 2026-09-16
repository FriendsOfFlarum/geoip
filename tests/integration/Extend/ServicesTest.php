<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\integration\Extend;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\Services\IPApi;
use FoF\GeoIP\Api\Services\MaxMindDb;
use FoF\GeoIP\Extend\Services;
use FoF\GeoIP\Tests\fixtures\MmdbBuilder;
use PHPUnit\Framework\Attributes\Test;

/**
 * The Services extender lets an image or a site pin the lookup driver and
 * configure it in code, so a deployment that ships databases works out of the
 * box and survives a settings reset.
 */
class ServicesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        // The extender writes to static state, which would otherwise leak
        // between tests and make results depend on execution order.
        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->dir = sys_get_temp_dir().'/fof-geoip-ext-'.bin2hex(random_bytes(6));
        mkdir($this->dir);

        (new MmdbBuilder('DBIP-Country-Lite'))
            ->add('8.8.8.0', 24, ['country' => ['iso_code' => 'US']])
            ->write($this->dir.'/country.mmdb');
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/country.mmdb');
        @rmdir($this->dir);

        parent::tearDown();
    }

    #[Test]
    public function the_offline_driver_is_registered_by_default(): void
    {
        $this->app();

        $this->assertArrayHasKey('maxmind', GeoIP::$services);
        $this->assertSame(MaxMindDb::class, GeoIP::$services['maxmind']);
    }

    #[Test]
    public function forcing_a_service_overrides_the_configured_setting(): void
    {
        $this->setting('fof-geoip.service', 'ipapi');

        $this->extend((new Services())->force('maxmind'));

        $geoip = $this->app()->getContainer()->make(GeoIP::class);

        $this->assertInstanceOf(MaxMindDb::class, $geoip->getService());
        $this->assertSame('maxmind', $geoip->getServiceName());
        $this->assertTrue($geoip->isForced());
    }

    #[Test]
    public function without_forcing_the_setting_still_decides(): void
    {
        $this->setting('fof-geoip.service', 'ipapi');

        $geoip = $this->app()->getContainer()->make(GeoIP::class);

        $this->assertInstanceOf(IPApi::class, $geoip->getService());
        $this->assertFalse($geoip->isForced());
    }

    #[Test]
    public function configure_supplies_database_paths_in_code(): void
    {
        $this->extend(
            (new Services())
                ->force('maxmind')
                ->configure('maxmind', ['country' => $this->dir.'/country.mmdb'])
        );

        /** @var MaxMindDb $service */
        $service = $this->app()->getContainer()->make(GeoIP::class)->getService();

        $this->assertTrue($service->isAvailable());
        $this->assertSame('US', $service->get('8.8.8.8')?->getCountryCode());
    }

    #[Test]
    public function configured_paths_take_precedence_over_settings(): void
    {
        // A stale or wrong path in the database must not defeat a path the
        // deployment pinned in code.
        $this->setting('fof-geoip.services.maxmind.country_path', '/nonexistent/country.mmdb');

        $this->extend(
            (new Services())
                ->force('maxmind')
                ->configure('maxmind', ['country' => $this->dir.'/country.mmdb'])
        );

        /** @var MaxMindDb $service */
        $service = $this->app()->getContainer()->make(GeoIP::class)->getService();

        $this->assertTrue($service->isAvailable());
    }

    #[Test]
    public function settings_supply_paths_when_the_extender_does_not(): void
    {
        $this->setting('fof-geoip.service', 'maxmind');
        $this->setting('fof-geoip.services.maxmind.country_path', $this->dir.'/country.mmdb');

        /** @var MaxMindDb $service */
        $service = $this->app()->getContainer()->make(GeoIP::class)->getService();

        $this->assertInstanceOf(MaxMindDb::class, $service);
        $this->assertTrue($service->isAvailable());
        $this->assertSame('US', $service->get('8.8.8.8')?->getCountryCode());
    }

    /**
     * configure() is not offline-only: an image that ships an API key for a
     * hosted provider should be able to pin it the same way it pins database
     * paths. Accepting the call and silently ignoring it would be worse than
     * not supporting it at all.
     */
    #[Test]
    public function configure_supplies_an_api_key_for_an_http_service(): void
    {
        $this->extend(
            (new Services())
                ->force('ipapi-pro')
                ->configure('ipapi-pro', ['access_key' => 'pinned-key'])
        );

        $geoip = $this->app()->getContainer()->make(GeoIP::class);

        $this->assertSame('pinned-key', $geoip->config('ipapi-pro', 'access_key'));
    }

    #[Test]
    public function a_configured_api_key_takes_precedence_over_the_setting(): void
    {
        $this->setting('fof-geoip.services.ipapi-pro.access_key', 'stored-key');

        $this->extend(
            (new Services())->configure('ipapi-pro', ['access_key' => 'pinned-key'])
        );

        $geoip = $this->app()->getContainer()->make(GeoIP::class);

        $this->assertSame('pinned-key', $geoip->config('ipapi-pro', 'access_key'));
    }

    #[Test]
    public function the_setting_is_used_when_nothing_is_pinned(): void
    {
        $this->setting('fof-geoip.services.ipapi-pro.access_key', 'stored-key');

        $geoip = $this->app()->getContainer()->make(GeoIP::class);

        $this->assertSame('stored-key', $geoip->config('ipapi-pro', 'access_key'));
    }

    /**
     * A pinned key is read by the service itself, not merely stored — this is
     * the assertion that would have caught configure() being a no-op for
     * anything other than the offline driver.
     */
    #[Test]
    public function an_http_service_reads_the_pinned_key(): void
    {
        $this->extend(
            (new Services())
                ->force('ipapi-pro')
                ->configure('ipapi-pro', ['access_key' => 'pinned-key'])
        );

        $service = $this->app()->getContainer()->make(GeoIP::class)->getService();

        // setAccessible() is a no-op since PHP 8.1 and deprecated in 8.5.
        $this->assertSame('pinned-key', (new \ReflectionMethod($service, 'apiKey'))->invoke($service));
    }

    /**
     * getService() was constructing a fresh driver on every call, so the
     * offline driver reopened all three .mmdb files each time — and a single
     * GeoIP::get() calls it twice, once via isAvailable(). Measured at 0.44ms
     * per lookup against 0.013ms when the instance is reused: 33x, all of it
     * file opening rather than lookups.
     */
    #[Test]
    public function the_resolved_service_is_reused(): void
    {
        $this->setting('fof-geoip.service', 'maxmind');
        $this->setting('fof-geoip.services.maxmind.country_path', $this->dir.'/country.mmdb');

        $geoip = $this->app()->getContainer()->make(GeoIP::class);

        $this->assertSame($geoip->getService(), $geoip->getService());
    }

    /**
     * Reuse must not outlive a configuration change, or an admin saving a new
     * path would keep getting the old driver until the next request.
     */
    #[Test]
    public function changing_the_configuration_rebuilds_the_service(): void
    {
        $this->setting('fof-geoip.service', 'ipapi');

        $geoip = $this->app()->getContainer()->make(GeoIP::class);
        $first = $geoip->getService();

        GeoIP::force('maxmind');

        $this->assertNotSame($first, $geoip->getService());
        $this->assertInstanceOf(MaxMindDb::class, $geoip->getService());
    }

    #[Test]
    public function a_third_party_service_can_be_registered(): void
    {
        $this->extend((new Services())->register('custom', CustomTestService::class));

        $this->app();

        $this->assertArrayHasKey('custom', GeoIP::$services);
        $this->assertSame(CustomTestService::class, GeoIP::$services['custom']);
    }
}
