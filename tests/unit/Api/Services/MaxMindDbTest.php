<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\unit\Api\Services;

use FoF\GeoIP\Api\Services\MaxMindDb;
use FoF\GeoIP\Tests\fixtures\MmdbBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The offline driver reads local MaxMind-format databases.
 *
 * Both vendors are covered: MaxMind's GeoLite2 and DB-IP's Lite editions
 * publish compatible schemas (DB-IP's ASN database even declares
 * `compat=GeoLite2-ASN`), so one code path serves both. The differences that
 * matter in practice — DB-IP Lite carries no postal data — are asserted
 * explicitly rather than assumed.
 *
 * Fixtures are built in memory: real databases are 8-130MB, too large to
 * commit, and downloading them would put the network in CI's path.
 */
class MaxMindDbTest extends TestCase
{
    private string $dir;

    /** @var string[] */
    private array $written = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/fof-geoip-'.bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach ($this->written as $file) {
            @unlink($file);
        }

        @rmdir($this->dir);

        parent::tearDown();
    }

    private function path(string $name): string
    {
        return $this->written[] = $this->dir.'/'.$name;
    }

    private function countryDb(string $type = 'DBIP-Country-Lite'): string
    {
        return (new MmdbBuilder($type))
            ->add('8.8.8.0', 24, [
                'country'   => ['iso_code' => 'US'],
                'continent' => ['code' => 'NA'],
            ])
            ->add('2a02:390::', 32, [
                'country'   => ['iso_code' => 'GB'],
                'continent' => ['code' => 'EU'],
            ])
            ->write($this->path('country.mmdb'));
    }

    /** DB-IP City Lite: location and subdivision, but no postal code. */
    private function dbipCityDb(): string
    {
        return (new MmdbBuilder('DBIP-City-Lite'))
            ->add('8.8.8.0', 24, [
                'city'         => ['names' => ['en' => 'Mountain View']],
                'country'      => ['iso_code' => 'US'],
                'location'     => ['latitude' => 37.386, 'longitude' => -122.0838],
                'subdivisions' => [['names' => ['en' => 'California']]],
            ])
            ->write($this->path('city.mmdb'));
    }

    /** MaxMind GeoLite2 City: the same, plus postal. */
    private function maxmindCityDb(): string
    {
        return (new MmdbBuilder('GeoLite2-City'))
            ->add('8.8.8.0', 24, [
                'city'         => ['names' => ['en' => 'Mountain View']],
                'country'      => ['iso_code' => 'US'],
                'location'     => ['latitude' => 37.386, 'longitude' => -122.0838],
                'postal'       => ['code' => '94035'],
                'subdivisions' => [['names' => ['en' => 'California']]],
            ])
            ->write($this->path('city-mm.mmdb'));
    }

    private function asnDb(): string
    {
        return (new MmdbBuilder('DBIP-ASN-Lite (compat=GeoLite2-ASN)'))
            ->add('8.8.8.0', 24, [
                'autonomous_system_number'       => 15169,
                'autonomous_system_organization' => 'Google LLC',
            ])
            ->write($this->path('asn.mmdb'));
    }

    private function driver(array $paths): MaxMindDb
    {
        return new MaxMindDb($paths, new NullLogger());
    }

    #[Test]
    public function it_is_an_offline_service(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        $this->assertTrue($driver->isOffline());
        $this->assertFalse($driver->batchSupported());
    }

    #[Test]
    public function it_resolves_a_country_from_the_country_database(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('US', $response->getCountryCode());
        $this->assertSame('8.8.8.8', $response->getIP());
    }

    #[Test]
    public function it_resolves_ipv6(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        $response = $driver->get('2a02:390:9e5c:beef:5ccf:5001:120e:8cc0');

        $this->assertNotNull($response);
        $this->assertSame('GB', $response->getCountryCode());
    }

    #[Test]
    public function it_merges_country_city_and_asn_databases(): void
    {
        $driver = $this->driver([
            'country' => $this->countryDb(),
            'city'    => $this->maxmindCityDb(),
            'asn'     => $this->asnDb(),
        ]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('US', $response->getCountryCode());
        $this->assertSame('94035', $response->getZipCode());
        $this->assertSame('37.386', (string) $response->getLatitude());
        $this->assertSame('-122.0838', (string) $response->getLongitude());
        $this->assertSame('AS15169 Google LLC', $response->getAs());
        $this->assertSame('Google LLC', $response->getOrganization());
    }

    /**
     * DB-IP's Lite editions carry no postal data at all — verified against the
     * real databases across several continents. The driver must degrade to a
     * null zip rather than inventing one or failing.
     */
    #[Test]
    public function it_tolerates_a_city_database_without_postal_data(): void
    {
        $driver = $this->driver([
            'country' => $this->countryDb(),
            'city'    => $this->dbipCityDb(),
        ]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertNull($response->getZipCode());
        // Location is still present, so the map keeps working.
        $this->assertSame('37.386', (string) $response->getLatitude());
    }

    /**
     * The city databases carry a place name and a subdivision, which the
     * driver previously discarded. Both vendors expose them the same way, so
     * one mapping serves both.
     */
    #[Test]
    public function it_captures_the_city_and_region(): void
    {
        $driver = $this->driver(['city' => $this->dbipCityDb()]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('Mountain View', $response->getCity());
        $this->assertSame('California', $response->getRegion());
    }

    #[Test]
    public function it_captures_the_city_and_region_from_a_maxmind_database(): void
    {
        $driver = $this->driver(['city' => $this->maxmindCityDb()]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('Mountain View', $response->getCity());
        $this->assertSame('California', $response->getRegion());
    }

    /**
     * Country-only databases carry neither, and a city database may omit the
     * subdivision for places that have none. Both must read back as null
     * rather than erroring.
     */
    #[Test]
    public function city_and_region_are_null_when_the_database_does_not_supply_them(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertNull($response->getCity());
        $this->assertNull($response->getRegion());
    }

    #[Test]
    public function a_city_without_a_subdivision_still_resolves(): void
    {
        $path = (new MmdbBuilder('DBIP-City-Lite'))
            ->add('8.8.8.0', 24, [
                'city'    => ['names' => ['en' => 'Singapore']],
                'country' => ['iso_code' => 'SG'],
            ])
            ->write($this->path('city-nosub.mmdb'));

        $response = $this->driver(['city' => $path])->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('Singapore', $response->getCity());
        $this->assertNull($response->getRegion());
    }

    #[Test]
    public function city_database_alone_is_enough_for_a_country(): void
    {
        // The city databases embed country data, so a country database is
        // optional when a city one is configured.
        $driver = $this->driver(['city' => $this->dbipCityDb()]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('US', $response->getCountryCode());
    }

    /**
     * Private and reserved ranges are absent from the databases, but they are
     * not a lookup failure — the answer is "this address has no location", and
     * it will never change.
     *
     * ip-api reports these as `status: fail` with a "private range" message,
     * which IPApi deliberately treats as a non-error and stores with that text
     * as the ISP. The offline driver matches that: a stored record marks the
     * address as resolved, so it is not re-queued on every render.
     */
    #[Test]
    public function a_private_address_resolves_to_a_record_describing_it(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        // ip-api distinguishes the two, and so do we: RFC 1918 space is
        // "private range", loopback and other reserved blocks are "reserved
        // range".
        $expected = [
            '192.168.65.1' => 'private range',
            '10.0.0.1'     => 'private range',
            '127.0.0.1'    => 'reserved range',
        ];

        foreach ($expected as $ip => $description) {
            $response = $driver->get($ip);

            $this->assertNotNull($response, "$ip should resolve rather than miss");
            $this->assertSame($ip, $response->getIP());
            $this->assertNull($response->getCountryCode());
            $this->assertSame($description, $response->getIsp());
        }
    }

    /**
     * A public address the databases do not cover is a genuine miss: coverage
     * may improve with a later edition, so nothing is stored.
     */
    #[Test]
    public function it_returns_null_for_a_public_address_missing_from_the_database(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        $this->assertNull($driver->get('1.1.1.1'));
    }

    #[Test]
    public function it_returns_null_for_a_malformed_address(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        // The reader throws InvalidArgumentException; the driver must absorb
        // it rather than let it escape into post serialization.
        $this->assertNull($driver->get('not-an-ip'));
    }

    #[Test]
    public function it_returns_null_when_no_databases_are_configured(): void
    {
        $driver = $this->driver([]);

        $this->assertNull($driver->get('8.8.8.8'));
        $this->assertFalse($driver->isAvailable());
    }

    #[Test]
    public function it_ignores_a_path_that_does_not_exist(): void
    {
        $driver = $this->driver([
            'country' => $this->countryDb(),
            'city'    => $this->dir.'/missing.mmdb',
        ]);

        // The usable database still answers.
        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('US', $response->getCountryCode());
    }

    #[Test]
    public function it_ignores_a_file_that_is_not_a_database(): void
    {
        $junk = $this->path('junk.mmdb');
        file_put_contents($junk, 'this is not an mmdb file');

        $driver = $this->driver([
            'country' => $this->countryDb(),
            'asn'     => $junk,
        ]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('US', $response->getCountryCode());
        $this->assertNull($response->getAs());
    }

    /**
     * A database with no path configured is reported as unconfigured rather
     * than as an error: the admin panel must be able to tell "you have not set
     * this" apart from "the file you set is broken", and all three databases
     * are legitimately optional.
     */
    #[Test]
    public function an_unconfigured_database_is_reported_as_such(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        $status = $driver->databaseStatus();

        $this->assertFalse($status['asn']['available']);
        $this->assertNull($status['asn']['path']);
        $this->assertNull($status['asn']['error']);
        $this->assertFalse($status['asn']['configured']);

        // Contrast with one that is configured and working.
        $this->assertTrue($status['country']['configured']);
    }

    #[Test]
    public function a_configured_but_missing_file_is_distinguishable_from_an_unset_one(): void
    {
        $driver = $this->driver([
            'country' => $this->countryDb(),
            'city'    => $this->dir.'/missing.mmdb',
        ]);

        $status = $driver->databaseStatus();

        // Configured, but unusable — an error the admin should see.
        $this->assertTrue($status['city']['configured']);
        $this->assertFalse($status['city']['available']);
        $this->assertNotNull($status['city']['error']);

        // Not configured at all — nothing to report.
        $this->assertFalse($status['asn']['configured']);
        $this->assertNull($status['asn']['error']);
    }

    #[Test]
    public function it_reports_the_databases_it_loaded(): void
    {
        $driver = $this->driver([
            'country' => $this->countryDb(),
            'asn'     => $this->asnDb(),
            'city'    => $this->dir.'/missing.mmdb',
        ]);

        $status = $driver->databaseStatus();

        $this->assertSame('DBIP-Country-Lite', $status['country']['type']);
        $this->assertTrue($status['country']['available']);
        $this->assertIsInt($status['country']['built']);

        $this->assertSame('DBIP-ASN-Lite (compat=GeoLite2-ASN)', $status['asn']['type']);

        $this->assertFalse($status['city']['available']);
        $this->assertNotNull($status['city']['error']);
    }

    #[Test]
    public function it_records_the_data_provider_from_the_database_type(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertStringContainsString('DBIP', (string) $response->getDataProvider());
    }

    #[Test]
    public function it_reads_a_maxmind_branded_database_the_same_way(): void
    {
        $driver = $this->driver(['country' => $this->countryDb('GeoLite2-Country')]);

        $response = $driver->get('8.8.8.8');

        $this->assertNotNull($response);
        $this->assertSame('US', $response->getCountryCode());
        $this->assertStringContainsString('GeoLite2', (string) $response->getDataProvider());
    }

    #[Test]
    public function batch_lookups_resolve_each_address(): void
    {
        $driver = $this->driver(['country' => $this->countryDb()]);

        $responses = $driver->getBatch(['8.8.8.8', '2a02:390::1', '1.1.1.1']);

        // Unknown addresses are omitted rather than returned as empty records.
        $this->assertCount(2, $responses);
        $this->assertSame('US', $responses[0]->getCountryCode());
        $this->assertSame('GB', $responses[1]->getCountryCode());
    }
}
