<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\integration\Command;

use Flarum\Testing\integration\TestCase;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Command\FetchIPInfo;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Tests\fixtures\MmdbBuilder;
use Illuminate\Contracts\Bus\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * What a lookup stores has to be writable without relying on $fillable.
 *
 * A response offers three keys that are not columns — `ip`, `threat_type` and
 * `fake` — and handing the whole object to the model only worked because mass
 * assignment dropped them. That is a guard, not a mapping: it hid the fact
 * that `threat_type` never reached `threat_types`, and the moment anything
 * wrote without it the insert failed with "Unknown column 'ip'".
 *
 * The first test exercises the real dispatch path with the model unguarded,
 * so a key that is not a column reaches SQL and fails loudly rather than
 * being silently swallowed.
 */
class StoredAttributesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->dir = sys_get_temp_dir().'/fof-geoip-store-'.bin2hex(random_bytes(6));
        mkdir($this->dir);

        (new MmdbBuilder('DBIP-City-Lite'))
            ->add('8.8.8.0', 24, [
                'country'      => ['iso_code' => 'US'],
                'city'         => ['names' => ['en' => 'Mountain View']],
                'subdivisions' => [['names' => ['en' => 'California']]],
            ])
            ->write($this->dir.'/city.mmdb');

        $this->setting('fof-geoip.service', 'maxmind');
        $this->setting('fof-geoip.services.maxmind.city_path', $this->dir.'/city.mmdb');
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/city.mmdb');
        @rmdir($this->dir);

        // Static on the model class, so it would otherwise stay unguarded for
        // every later test in the process.
        IPInfo::reguard();

        parent::tearDown();
    }

    /**
     * Unguarded, so nothing filters the attributes on their way to SQL. If a
     * key is not a column this throws, which is exactly what happened in
     * production once mass assignment was out of the way.
     */
    #[Test]
    public function a_lookup_stores_without_relying_on_mass_assignment_protection(): void
    {
        $bus = $this->app()->getContainer()->make(Dispatcher::class);

        IPInfo::unguard();

        $record = $bus->dispatch(new FetchIPInfo('8.8.8.8'));

        $this->assertTrue($record->exists, 'the lookup should have been stored');
        $this->assertSame('US', $record->country_code);
        $this->assertSame('8.8.8.8', $record->address);
    }

    /**
     * The address belongs in `address`; there is no `ip` column.
     */
    #[Test]
    public function the_address_and_place_are_stored_under_their_column_names(): void
    {
        $bus = $this->app()->getContainer()->make(Dispatcher::class);

        $bus->dispatch(new FetchIPInfo('8.8.8.8'));

        $stored = IPInfo::query()->find('8.8.8.8');

        $this->assertNotNull($stored);
        $this->assertSame('Mountain View', $stored->city);
        $this->assertSame('California', $stored->region);
    }
}
