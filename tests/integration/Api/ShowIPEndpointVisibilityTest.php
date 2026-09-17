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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

/**
 * Field-level visibility on the `/api/ip_info/{ip}` show endpoint.
 *
 * The frontend routes every IPAddress lookup through this endpoint, so it is
 * the path that must not leak gated fields. `AttachRelation` applies these
 * rules to `ip_info` embedded in a post payload, picking `BasicIPInfoSerializer`
 * or `IPInfoSerializer` per the actor's permissions; this endpoint has to make
 * the same choice, but with no post in scope.
 *
 * `viewIps` is post-scoped in 1.x (`PostPolicy::can()` delegates it to
 * `viewIpsPosts` on the discussion), so with no post to check against, the
 * global `discussion.viewIpsPosts` permission is the equivalent test.
 *
 * The IP is seeded into the database so these tests never call out to a live
 * geolocation provider.
 */
class ShowIPEndpointVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const IP = '1.2.3.4';

    /** Fields that must never reach an actor without discussion.viewIpsPosts. */
    private const SENSITIVE_FIELDS = [
        'ip', 'zipCode', 'latitude', 'longitude', 'isp', 'organization',
        'as', 'mobile', 'threatLevel', 'threatType', 'error', 'dataProvider',
        'createdAt', 'updatedAt',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(), // id=2, Members only — no extra permissions
                [
                    'id'                 => 3,
                    'username'           => 'canseecountry',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email'              => 'canseecountry@machine.local',
                    'is_email_confirmed' => 1,
                ],
                [
                    'id'                 => 5,
                    'username'           => 'moderator',
                    'password'           => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email'              => 'mod@machine.local',
                    'is_email_confirmed' => 1,
                ],
            ],
            'groups' => [
                ['id' => 10, 'name_singular' => 'Country Viewer', 'name_plural' => 'Country Viewers', 'color' => null, 'icon' => null],
            ],
            'group_permission' => [
                ['group_id' => 10, 'permission' => 'fof-geoip.canSeeCountry'],
                ['group_id' => 4, 'permission' => 'discussion.viewIpsPosts'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
                ['user_id' => 5, 'group_id' => 4],
            ],
            'ip_info' => [
                [
                    'address'       => self::IP,
                    'country_code'  => 'DE',
                    'zip_code'      => '10115',
                    'latitude'      => '52.5200',
                    'longitude'     => '13.4050',
                    'isp'           => 'Test ISP GmbH',
                    'organization'  => 'Test Org',
                    'as'            => 'AS12345 Test ISP GmbH',
                    'mobile'        => false,
                    'threat_level'  => '',
                    'threat_types'  => null,
                    'error'         => null,
                    'data_provider' => 'test',
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'updated_at'    => Carbon::now()->toDateTimeString(),
                ],
            ],
        ]);
    }

    private function showIpInfo(?int $userId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/ip_info/'.urlencode(self::IP), [
                'authenticatedAs' => $userId,
            ])
        );

        return [$response->getStatusCode(), json_decode($response->getBody(), true)];
    }

    /**
     * @test
     */
    public function guest_cannot_access_the_endpoint()
    {
        [$status, $body] = $this->showIpInfo(null);

        $this->assertNotEquals(200, $status, 'Guests must not be able to read ip_info');
        $this->assertArrayNotHasKey('data', $body ?? []);
    }

    /**
     * @test
     */
    public function user_without_can_see_country_gets_no_sensitive_fields()
    {
        [$status, $body] = $this->showIpInfo(2);

        // A plain authenticated user has neither discussion.viewIpsPosts nor
        // fof-geoip.canSeeCountry, so the endpoint rejects them outright.
        // The forum's opt-in country flag does not depend on this endpoint: it
        // reads the `ip_info` relation embedded in the post payload, which
        // AttachRelation gates separately, so refusing here costs it nothing.
        $this->assertEquals(403, $status, 'A user with no relevant permission must be refused');
        $this->assertArrayNotHasKey('data', $body ?? []);
    }

    /**
     * @test
     */
    public function can_see_country_actor_gets_country_code_but_no_sensitive_fields()
    {
        [$status, $body] = $this->showIpInfo(3);

        $this->assertEquals(200, $status);
        $this->assertEquals('DE', $body['data']['attributes']['countryCode']);

        foreach (self::SENSITIVE_FIELDS as $field) {
            $this->assertArrayNotHasKey(
                $field,
                $body['data']['attributes'],
                "Field `$field` leaked to a canSeeCountry actor"
            );
        }
    }

    /**
     * @test
     */
    public function view_ips_actor_gets_the_full_record()
    {
        [$status, $body] = $this->showIpInfo(5);

        $this->assertEquals(200, $status);

        $attributes = $body['data']['attributes'];

        $this->assertEquals(self::IP, $attributes['ip']);
        $this->assertEquals('DE', $attributes['countryCode']);
        $this->assertEquals('Test ISP GmbH', $attributes['isp']);
    }

    /**
     * @test
     */
    public function id_is_hashed_so_the_address_is_not_exposed_through_it()
    {
        // The frontend cannot key its lookup cache on this id without hashing,
        // which is why the cache in extendIpAddress is keyed by address
        // instead. Guard the hashing so that reasoning stays valid.
        [$status, $body] = $this->showIpInfo(3);

        $this->assertEquals(200, $status);
        $this->assertEquals(hash('sha256', self::IP), $body['data']['id']);
        $this->assertStringNotContainsString(self::IP, $body['data']['id']);
    }
}
