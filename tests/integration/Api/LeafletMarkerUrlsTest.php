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

class LeafletMarkerUrlsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function forum_exposes_leaflet_marker_urls()
    {
        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $attributes = json_decode($response->getBody(), true)['data']['attributes'];

        $this->assertArrayHasKey('fofGeoipLeafletMarkerUrls', $attributes);

        $urls = $attributes['fofGeoipLeafletMarkerUrls'];

        // The marker images must resolve to the extension's published assets so
        // bundled Leaflet doesn't try to load them relative to the JS bundle.
        $this->assertStringContainsString('/extensions/fof-geoip/marker-icon.png', $urls['iconUrl']);
        $this->assertStringContainsString('/extensions/fof-geoip/marker-icon-2x.png', $urls['iconRetinaUrl']);
        $this->assertStringContainsString('/extensions/fof-geoip/marker-shadow.png', $urls['shadowUrl']);
    }
}
