<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\unit\Api;

use FoF\GeoIP\Api\ServiceResponse;
use FoF\GeoIP\Model\IPInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * What a response offers for storage has to match what the table can take.
 *
 * `jsonSerialize()` returns every property, which is right for the admin
 * service tester — `ip` and `fake` are exactly what you want to see when a
 * provider misbehaves — but three of those keys are not columns:
 *
 *  - `ip`          the column is `address`
 *  - `threat_type` the column is `threat_types`
 *  - `fake`        a transport detail, never stored
 *
 * Passing them to the model only worked because $fillable silently dropped
 * them. That made the mismatch invisible until something bypassed mass
 * assignment, and it meant threat_type was never saved at all. Storage now
 * goes through toDatabaseAttributes(), which offers only real columns.
 */
class ServiceResponseColumnMappingTest extends TestCase
{
    private function response(): ServiceResponse
    {
        return (new ServiceResponse('DBIP-City-Lite'))
            ->setIP('8.8.8.8')
            ->setCountryCode('US')
            ->setCity('Mountain View')
            ->setRegion('California')
            ->setThreatType('spam');
    }

    /**
     * The guard rail: every key offered for storage must be a column. This is
     * the assertion that would have caught the original fault.
     */
    #[Test]
    public function every_attribute_offered_for_storage_is_a_real_column(): void
    {
        $columns = (new IPInfo())->getFillable();

        foreach (array_keys($this->response()->toDatabaseAttributes()) as $key) {
            $this->assertContains(
                $key,
                $columns,
                "`$key` is offered for storage but is not a column on ip_info"
            );
        }
    }

    #[Test]
    public function the_address_is_offered_under_its_column_name(): void
    {
        $attributes = $this->response()->toDatabaseAttributes();

        $this->assertSame('8.8.8.8', $attributes['address'] ?? null);
        $this->assertArrayNotHasKey('ip', $attributes, '`ip` is not a column; the address column is `address`');
    }

    /**
     * Previously emitted as `threat_type`, which is not a column, so the value
     * was dropped on every save.
     */
    #[Test]
    public function the_threat_type_is_offered_under_its_column_name(): void
    {
        $attributes = $this->response()->toDatabaseAttributes();

        $this->assertSame('spam', $attributes['threat_types'] ?? null);
        $this->assertArrayNotHasKey('threat_type', $attributes);
    }

    /**
     * Whether a response was faked describes the lookup, not the address.
     */
    #[Test]
    public function the_fake_flag_is_not_offered_for_storage(): void
    {
        $this->assertArrayNotHasKey('fake', (new ServiceResponse('test', true))->toDatabaseAttributes());
    }

    #[Test]
    public function the_values_a_service_set_are_carried_through(): void
    {
        $attributes = $this->response()->toDatabaseAttributes();

        $this->assertSame('US', $attributes['country_code']);
        $this->assertSame('Mountain View', $attributes['city']);
        $this->assertSame('California', $attributes['region']);
        $this->assertSame('DBIP-City-Lite', $attributes['data_provider']);
    }

    /**
     * ipdata is the one service that reports threat types. Because the key
     * never matched the column, what it found was dropped on every save.
     */
    #[Test]
    public function a_threat_type_from_a_service_now_reaches_the_column(): void
    {
        $attributes = (new ServiceResponse('ipdata'))
            ->setIP('1.2.3.4')
            ->setThreatLevel('1')
            ->setThreatType('attacker')
            ->toDatabaseAttributes();

        $this->assertSame('attacker', $attributes['threat_types']);
        $this->assertSame('1', $attributes['threat_level']);
    }

    /**
     * The admin service tester shows the full object deliberately, so this
     * stays as it is — `ip` and `fake` are useful when diagnosing a provider.
     */
    #[Test]
    public function the_diagnostic_view_still_shows_everything(): void
    {
        $serialized = $this->response()->jsonSerialize();

        $this->assertArrayHasKey('ip', $serialized);
        $this->assertArrayHasKey('fake', $serialized);
    }
}
