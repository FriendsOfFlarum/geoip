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

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\GeoIP\Model\IPInfo;
use PHPUnit\Framework\Attributes\Test;

class IPInfoRelationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $this->setting('fof-geoip.showFlag', true);

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            'group_permission' => [
                ['group_id' => 3, 'permission' => 'fof-geoip.canSeeCountry'],
            ],
            Discussion::class => [
                [
                    'id'             => 1,
                    'title'          => 'IP Info Test Discussion',
                    'slug'           => 'ip-info-test-discussion',
                    'created_at'     => Carbon::now()->toDateTimeString(),
                    'last_posted_at' => Carbon::now()->toDateTimeString(),
                    'user_id'        => 1,
                    'first_post_id'  => 1,
                    'comment_count'  => 3,
                ],
            ],
            Post::class => [
                [
                    'id'            => 1,
                    'discussion_id' => 1,
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'user_id'       => 1,
                    'type'          => 'comment',
                    'content'       => '<t><p>Post from Switzerland</p></t>',
                    'ip_address'    => '146.70.126.214',
                ],
                [
                    'id'            => 2,
                    'discussion_id' => 1,
                    'created_at'    => Carbon::now()->addMinutes(5)->toDateTimeString(),
                    'user_id'       => 2,
                    'type'          => 'comment',
                    'content'       => '<t><p>Post from Austria</p></t>',
                    'ip_address'    => '193.43.158.225',
                ],
                [
                    'id'            => 3,
                    'discussion_id' => 1,
                    'created_at'    => Carbon::now()->addMinutes(10)->toDateTimeString(),
                    'user_id'       => 2,
                    'type'          => 'comment',
                    'content'       => '<t><p>Post from Austria IPv6</p></t>',
                    'ip_address'    => '2001:871:22d:6919:794f:61de:f988:1596',
                ],
            ],
            IPInfo::class => [
                [
                    'address'       => '146.70.126.214',
                    'mobile'        => false,
                    'country_code'  => 'CH',
                    'zip_code'      => '8010',
                    'latitude'      => '47.404399871826',
                    'longitude'     => '8.43630027771',
                    'isp'           => 'M247 Europe SRL',
                    'organization'  => 'M247 Europe SRL',
                    'as'            => 'AS9009 M247 Europe SRL',
                    'threat_level'  => '',
                    'threat_types'  => null,
                    'error'         => null,
                    'data_provider' => 'https://api.ipdata.co',
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'updated_at'    => Carbon::now()->toDateTimeString(),
                ],
                [
                    'address'       => '193.43.158.225',
                    'mobile'        => false,
                    'country_code'  => 'AT',
                    'zip_code'      => null,
                    'latitude'      => '48.208488464355',
                    'longitude'     => '16.372079849243',
                    'isp'           => 'Flughafen WIEN Aktiengesellschaft',
                    'organization'  => 'Flughafen WIEN Aktiengesellschaft',
                    'as'            => 'AS28771 Flughafen WIEN Aktiengesellschaft',
                    'threat_level'  => '',
                    'threat_types'  => null,
                    'error'         => null,
                    'data_provider' => 'https://api.ipdata.co',
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'updated_at'    => Carbon::now()->toDateTimeString(),
                ],
                [
                    'address'       => '2001:871:22d:6919:794f:61de:f988:1596',
                    'mobile'        => false,
                    'country_code'  => 'AT',
                    'zip_code'      => '2000',
                    'latitude'      => '48.379398345947',
                    'longitude'     => '16.215599060059',
                    'isp'           => 'A1 Telekom Austria Ag',
                    'organization'  => 'A1 Telekom Austria Ag',
                    'as'            => 'AS8447 A1 Telekom Austria Ag',
                    'threat_level'  => '',
                    'threat_types'  => null,
                    'error'         => null,
                    'data_provider' => 'https://api.ipdata.co',
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'updated_at'    => Carbon::now()->toDateTimeString(),
                ],
            ],
        ]);
    }

    #[Test]
    public function admin_can_include_ip_info_when_fetching_single_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/1', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        // Verify JSON:API structure
        $this->assertArrayHasKey('data', $body);
        $this->assertEquals('posts', $body['data']['type']);
        $this->assertEquals('1', $body['data']['id']);

        // Verify the post has ipInfo relationship
        $this->assertArrayHasKey('relationships', $body['data']);
        $this->assertArrayHasKey('ipInfo', $body['data']['relationships']);
        $this->assertEquals('ip_info', $body['data']['relationships']['ipInfo']['data']['type']);

        // Verify the included section contains IP info
        $this->assertArrayHasKey('included', $body);

        // Extract IP info from included resources (may contain other relationships like user, discussion)
        $ipInfoResources = array_values(array_filter($body['included'], function ($item) {
            return $item['type'] === 'ip_info';
        }));

        $this->assertCount(1, $ipInfoResources, 'Should have exactly 1 IP info resource');
        $ipInfo = $ipInfoResources[0];

        $this->assertEquals('ip_info', $ipInfo['type']);
        $this->assertArrayHasKey('id', $ipInfo);
        $this->assertArrayHasKey('attributes', $ipInfo);

        $this->assertEquals('CH', $ipInfo['attributes']['countryCode']);
        $this->assertEquals('8010', $ipInfo['attributes']['zipCode']);
        $this->assertEquals('M247 Europe SRL', $ipInfo['attributes']['isp']);
        $this->assertEquals('M247 Europe SRL', $ipInfo['attributes']['organization']);
    }

    #[Test]
    public function guest_cannot_see_ip_info_due_to_visibility_scope()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/1', [
                'authenticatedAs' => null,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertEquals('posts', $body['data']['type']);

        // Guests should not see IP info at all (blocked by ScopeIPInfoVisibility)
        // The included array might not exist, or if it does, shouldn't contain ip_info
        if (isset($body['included'])) {
            $ipInfoResources = array_values(array_filter($body['included'], function ($item) {
                return $item['type'] === 'ip_info';
            }));
            $this->assertCount(0, $ipInfoResources, 'Guests should not see any IP info');
        }

        // The relationship might be present in the data structure but without accessible included data
        // This is expected behavior - the relationship exists but visibility scoping prevents access
    }

    #[Test]
    public function normal_user_can_see_basic_ip_info_but_not_full_address()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('included', $body);

        $ipInfo = $body['included'][0];

        $this->assertEquals('ip_info', $ipInfo['type']);
        $this->assertArrayHasKey('attributes', $ipInfo);

        // canSeeCountry users see country code only
        $this->assertArrayHasKey('countryCode', $ipInfo['attributes']);
        $this->assertEquals('CH', $ipInfo['attributes']['countryCode']);

        // All other fields require viewIps permission
        foreach (['ip', 'zipCode', 'latitude', 'longitude', 'isp', 'organization', 'as', 'mobile', 'threatLevel', 'threatType', 'dataProvider'] as $field) {
            $this->assertArrayNotHasKey($field, $ipInfo['attributes'], "Field '{$field}' should not be visible to canSeeCountry users");
        }
    }

    #[Test]
    public function admin_can_see_full_ip_address_field()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/1', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('included', $body);
        $ipInfo = $body['included'][0];

        $this->assertEquals('ip_info', $ipInfo['type']);
        $this->assertArrayHasKey('attributes', $ipInfo);

        // Admin with viewIps permission should see the actual IP address field
        $this->assertArrayHasKey('ip', $ipInfo['attributes'], 'IP address field should be visible to users with viewIps permission');
        $this->assertEquals('146.70.126.214', $ipInfo['attributes']['ip']);

        // Verify all other fields are also visible
        $this->assertArrayHasKey('countryCode', $ipInfo['attributes']);
        $this->assertEquals('CH', $ipInfo['attributes']['countryCode']);
    }

    #[Test]
    public function ipv6_addresses_are_properly_handled()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/3', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('included', $body);
        $ipInfo = $body['included'][0];

        $this->assertEquals('ip_info', $ipInfo['type']);
        $this->assertArrayHasKey('attributes', $ipInfo);

        // Verify IPv6 address is stored and retrieved correctly
        $this->assertArrayHasKey('ip', $ipInfo['attributes']);
        $this->assertEquals('2001:871:22d:6919:794f:61de:f988:1596', $ipInfo['attributes']['ip']);

        // Verify geolocation data for IPv6 works
        $this->assertEquals('AT', $ipInfo['attributes']['countryCode']);
        $this->assertEquals('2000', $ipInfo['attributes']['zipCode']);
        $this->assertEquals('A1 Telekom Austria Ag', $ipInfo['attributes']['organization']);
    }

    #[Test]
    public function each_post_links_to_its_own_ip_info_record()
    {
        // Fetch post 1 (Switzerland)
        $response1 = $this->send(
            $this->request('GET', '/api/posts/1', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response1->getStatusCode());
        $body1 = json_decode($response1->getBody(), true);

        $this->assertArrayHasKey('included', $body1);
        $ipInfo1 = $body1['included'][0];

        $this->assertEquals('ip_info', $ipInfo1['type']);
        $this->assertEquals('CH', $ipInfo1['attributes']['countryCode']);
        $this->assertEquals('146.70.126.214', $ipInfo1['attributes']['ip']);

        // Fetch post 2 (Austria)
        $response2 = $this->send(
            $this->request('GET', '/api/posts/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response2->getStatusCode());
        $body2 = json_decode($response2->getBody(), true);

        $this->assertArrayHasKey('included', $body2);
        $ipInfo2 = $body2['included'][0];

        $this->assertEquals('ip_info', $ipInfo2['type']);
        $this->assertEquals('AT', $ipInfo2['attributes']['countryCode']);
        $this->assertEquals('193.43.158.225', $ipInfo2['attributes']['ip']);

        // Verify the posts have different IP addresses and different countries
        $this->assertNotEquals($ipInfo1['attributes']['ip'], $ipInfo2['attributes']['ip']);
        $this->assertNotEquals($ipInfo1['attributes']['countryCode'], $ipInfo2['attributes']['countryCode']);
    }
}
