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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * City and region are optional everywhere.
 *
 * Only some services can supply them — ip-api and the offline databases can,
 * IPLocation and IPInfoLite cannot — so every consumer has to cope with their
 * absence. A service that never sets them must still produce a response that
 * saves cleanly, which is what keeps the other drivers working unchanged.
 */
class ServiceResponseCityRegionTest extends TestCase
{
    #[Test]
    public function they_default_to_null(): void
    {
        $response = new ServiceResponse('test');

        $this->assertNull($response->getCity());
        $this->assertNull($response->getRegion());
    }

    #[Test]
    public function a_response_without_them_still_serializes(): void
    {
        // Mirrors IPLocation, which sets only a country and an ISP.
        $response = (new ServiceResponse('iplocation'))
            ->setIP('8.8.8.8')
            ->setCountryCode('US')
            ->setIsp('Example ISP');

        $json = $response->toJson();

        // Present as keys so a mass-assign writes explicit nulls rather than
        // leaving stale values behind on a refresh.
        $this->assertArrayHasKey('city', $json);
        $this->assertArrayHasKey('region', $json);
        $this->assertNull($json['city']);
        $this->assertNull($json['region']);
    }

    #[Test]
    public function they_round_trip_when_set(): void
    {
        $response = (new ServiceResponse('test'))
            ->setCity('Ashburn')
            ->setRegion('Virginia');

        $this->assertSame('Ashburn', $response->getCity());
        $this->assertSame('Virginia', $response->getRegion());

        $json = $response->toJson();

        $this->assertSame('Ashburn', $json['city']);
        $this->assertSame('Virginia', $json['region']);
    }

    /**
     * "Unknown" and "not a mobile connection" are different claims. The
     * offline databases carry no connection-type data at all, so reporting
     * false would assert something the data does not support — the UI would
     * show "Cellular network: No" for every address.
     */
    #[Test]
    public function mobile_is_null_until_a_service_states_otherwise(): void
    {
        $response = new ServiceResponse('test');

        $this->assertNull($response->getMobile());
        $this->assertNull($response->toJson()['mobile']);
    }

    #[Test]
    public function mobile_round_trips_when_a_service_knows(): void
    {
        $this->assertTrue((new ServiceResponse('test'))->setMobile(true)->getMobile());
        $this->assertFalse((new ServiceResponse('test'))->setMobile(false)->getMobile());

        // An explicit "we do not know" is also expressible.
        $this->assertNull((new ServiceResponse('test'))->setMobile(null)->getMobile());
    }

    #[Test]
    public function empty_strings_are_normalised_to_null(): void
    {
        // ip-api returns "" rather than omitting the field when it has no
        // city for an address; an empty string in the column would render as
        // a blank line in the UI.
        $response = (new ServiceResponse('test'))
            ->setCity('')
            ->setRegion('');

        $this->assertNull($response->getCity());
        $this->assertNull($response->getRegion());
    }
}
