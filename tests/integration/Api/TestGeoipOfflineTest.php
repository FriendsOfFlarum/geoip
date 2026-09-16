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
use FoF\GeoIP\Tests\fixtures\MmdbBuilder;
use PHPUnit\Framework\Attributes\Test;

/**
 * The service tester against an offline driver.
 *
 * It was written for HTTP services and reflects into `buildUrl()` and the
 * Guzzle client to show the raw exchange. An offline driver has neither, so
 * that reflection threw and a perfectly successful lookup was reported as an
 * error. The tester must describe what an offline lookup actually did — which
 * databases answered — instead of inventing an HTTP request that never happened.
 */
class TestGeoipOfflineTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->dir = sys_get_temp_dir().'/fof-geoip-test-'.bin2hex(random_bytes(6));
        mkdir($this->dir);

        (new MmdbBuilder('DBIP-Country-Lite'))
            ->add('8.8.8.0', 24, ['country' => ['iso_code' => 'US']])
            ->write($this->dir.'/country.mmdb');

        $this->prepareDatabase([\Flarum\User\User::class => [$this->normalUser()]]);

        $this->setting('fof-geoip.service', 'maxmind');
        $this->setting('fof-geoip.services.maxmind.country_path', $this->dir.'/country.mmdb');
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/country.mmdb');
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function test(string $ip): array
    {
        $response = $this->send(
            $this->request('GET', '/api/geoip/test', ['authenticatedAs' => 1])
                ->withQueryParams(['ip' => $ip])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody(), true)['data']['attributes'];
    }

    #[Test]
    public function a_successful_offline_lookup_is_reported_as_a_success(): void
    {
        $attributes = $this->test('8.8.8.8');

        $this->assertTrue($attributes['success'], 'A resolved address must not be reported as an error');
        $this->assertNull($attributes['error']);
        $this->assertSame('US', $attributes['processed_response']['country_code']);
    }

    #[Test]
    public function it_does_not_invent_an_http_exchange(): void
    {
        $attributes = $this->test('8.8.8.8');

        // There is no request, status code or headers in an offline lookup;
        // reporting empty ones as failures is what produced the false error.
        $this->assertArrayNotHasKey('raw_http_response', $attributes);
        $this->assertArrayNotHasKey('response_headers', $attributes);
        $this->assertArrayNotHasKey('http_status_code', $attributes);
        $this->assertArrayNotHasKey('request_url', $attributes);
    }

    #[Test]
    public function it_reports_which_databases_answered(): void
    {
        $attributes = $this->test('8.8.8.8');

        $this->assertArrayHasKey('databases', $attributes);
        $this->assertTrue($attributes['databases']['country']['available']);
        $this->assertSame('DBIP-Country-Lite', $attributes['databases']['country']['type']);
        $this->assertFalse($attributes['databases']['asn']['configured']);
    }

    /**
     * Testing a private address proves the configuration works — the driver
     * correctly identified it as unlocatable. Reporting that in red as an
     * "Error" wrongly suggests something is broken, so it is a success with an
     * explanatory note, matching how the HTTP services treat the same case.
     */
    #[Test]
    public function a_private_address_is_a_success_with_a_note(): void
    {
        $attributes = $this->test('192.168.1.1');

        $this->assertTrue($attributes['success'], 'A private address is not a configuration failure');
        $this->assertNull($attributes['error']);
        $this->assertNotNull($attributes['notice']);
        $this->assertStringContainsString('private', strtolower($attributes['notice']));
        $this->assertSame('private range', $attributes['processed_response']['isp']);
    }

    /**
     * A public address the databases do not cover is a real miss worth
     * flagging — the database may be too old or the wrong edition.
     */
    #[Test]
    public function an_uncovered_public_address_is_reported_as_a_miss(): void
    {
        $attributes = $this->test('1.1.1.1');

        $this->assertFalse($attributes['success']);
        $this->assertNotNull($attributes['error']);
    }

    #[Test]
    public function a_driver_with_no_databases_reports_that_clearly(): void
    {
        $this->setting('fof-geoip.services.maxmind.country_path', '');

        $attributes = $this->test('8.8.8.8');

        $this->assertFalse($attributes['success']);
        $this->assertNotNull($attributes['error']);
    }
}
