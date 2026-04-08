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

/**
 * Tests the field-level visibility rules for ip_info:
 *
 * - Guests:                   no ip_info relationship at all
 * - Regular users:            no ip_info (not canSeeCountry, showFlag off)
 * - canSeeCountry users:      countryCode only — no ip, latitude, longitude, isp, etc.
 * - showFlag + showIPCountry: countryCode only — no ip, latitude, longitude, isp, etc.
 * - viewIps users (admin):    all fields including ip
 *
 * Also tests that fofGeoipCanSeeIpInfo on the forum resource returns the
 * correct value for each actor type, without leaking data.
 */
class IpInfoVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    // Full set of sensitive fields that must never leak to non-viewIps actors
    private const SENSITIVE_FIELDS = ['ip', 'latitude', 'longitude', 'isp', 'organization', 'as', 'mobile', 'threatLevel', 'threatType', 'dataProvider'];

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),                                                                                        // id=2, Members group only (no extra permissions)
                ['id' => 3, 'username' => 'canseecountry', 'email' => 'canseecountry@example.com', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'showflaguser',  'email' => 'showflag@example.com',      'is_email_confirmed' => 1, 'preferences' => json_encode(['showIPCountry' => true])],
                ['id' => 5, 'username' => 'moderator',     'email' => 'mod@example.com',            'is_email_confirmed' => 1],
            ],
            'groups' => [
                ['id' => 10, 'name_singular' => 'Country Viewer', 'name_plural' => 'Country Viewers', 'color' => null, 'icon' => null, 'is_hidden' => 0],
            ],
            'group_permission' => [
                ['group_id' => 10, 'permission' => 'fof-geoip.canSeeCountry'], // Custom group gets canSeeCountry only
                ['group_id' => 4,  'permission' => 'discussion.viewIpsPosts'], // Mods group gets viewIps
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10], // canseecountry → Country Viewers (canSeeCountry only, no viewIps)
                // user 4 (showflaguser) has no extra group — relies purely on showFlag+preference
                // user 2 (normalUser) has Members group only — no extra permissions
                ['user_id' => 5, 'group_id' => 4],  // moderator → Mods (viewIps)
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Visibility Test', 'slug' => 'visibility-test', 'created_at' => Carbon::now()->toDateTimeString(), 'last_posted_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
            ],
            Post::class => [
                // Post 1: authored by admin (user 1) — used for most permission tests
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(),               'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>test post</p></t>',             'ip_address' => '1.2.3.4'],
                // Post 2: authored by showflaguser (user 4, showIPCountry=true) — used for showFlag preference tests
                ['id' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now()->addMinutes(1)->toDateTimeString(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>post by showflag user</p></t>', 'ip_address' => '5.6.7.8'],
            ],
            IPInfo::class => [
                [
                    'address'       => '1.2.3.4',
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
                [
                    'address'       => '5.6.7.8',
                    'country_code'  => 'FR',
                    'zip_code'      => '75001',
                    'latitude'      => '48.8566',
                    'longitude'     => '2.3522',
                    'isp'           => 'Test ISP FR',
                    'organization'  => 'Test Org FR',
                    'as'            => 'AS99999 Test ISP FR',
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getPostWithIpInfo(int $postId, ?int $userId): array
    {
        $response = $this->send(
            $this->request('GET', "/api/posts/{$postId}", ['authenticatedAs' => $userId])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody(), true);
    }

    private function extractIpInfo(array $body): ?array
    {
        if (!isset($body['included'])) {
            return null;
        }

        $ipInfoItems = array_values(array_filter($body['included'], fn ($item) => $item['type'] === 'ip_info'));

        return $ipInfoItems[0] ?? null;
    }

    private function getForumData(?int $userId): array
    {
        $response = $this->send(
            $this->request('GET', '/api', ['authenticatedAs' => $userId])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody(), true);
    }

    // -------------------------------------------------------------------------
    // fofGeoipCanSeeIpInfo forum attribute
    // -------------------------------------------------------------------------

    #[Test]
    public function forum_attribute_is_false_for_guest()
    {
        $body = $this->getForumData(null);
        $this->assertFalse($body['data']['attributes']['fofGeoipCanSeeIpInfo']);
    }

    #[Test]
    public function forum_attribute_is_false_for_regular_user_when_showflag_off()
    {
        $body = $this->getForumData(2);
        $this->assertFalse($body['data']['attributes']['fofGeoipCanSeeIpInfo']);
    }

    #[Test]
    public function forum_attribute_is_true_for_can_see_country_user()
    {
        $body = $this->getForumData(3);
        $this->assertTrue($body['data']['attributes']['fofGeoipCanSeeIpInfo']);
    }

    #[Test]
    public function forum_attribute_is_true_when_showflag_enabled()
    {
        $this->setting('fof-geoip.showFlag', true);

        $body = $this->getForumData(2);
        $this->assertTrue($body['data']['attributes']['fofGeoipCanSeeIpInfo']);
    }

    #[Test]
    public function forum_attribute_is_true_for_viewips_user()
    {
        $body = $this->getForumData(5);
        $this->assertTrue($body['data']['attributes']['fofGeoipCanSeeIpInfo']);
    }

    #[Test]
    public function forum_attribute_is_true_for_admin()
    {
        $body = $this->getForumData(1);
        $this->assertTrue($body['data']['attributes']['fofGeoipCanSeeIpInfo']);
    }

    // -------------------------------------------------------------------------
    // Guest — no ip_info at all
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_sees_no_ip_info()
    {
        $body = $this->getPostWithIpInfo(1, null);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNull($ipInfo, 'Guest should not receive any ip_info resource');
    }

    // -------------------------------------------------------------------------
    // Regular user (no permissions, showFlag off) — no ip_info
    // -------------------------------------------------------------------------

    #[Test]
    public function regular_user_sees_no_ip_info_when_showflag_off()
    {
        $body = $this->getPostWithIpInfo(1, 2);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNull($ipInfo, 'Regular user should not receive ip_info when showFlag is off and no permission');
    }

    // -------------------------------------------------------------------------
    // canSeeCountry user — countryCode only, no sensitive fields
    // -------------------------------------------------------------------------

    #[Test]
    public function can_see_country_user_receives_country_code()
    {
        $body = $this->getPostWithIpInfo(1, 3);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNotNull($ipInfo, 'canSeeCountry user should receive ip_info');
        $this->assertArrayHasKey('countryCode', $ipInfo['attributes']);
        $this->assertEquals('DE', $ipInfo['attributes']['countryCode']);
    }

    #[Test]
    public function can_see_country_user_does_not_receive_sensitive_fields()
    {
        $body = $this->getPostWithIpInfo(1, 3);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNotNull($ipInfo);

        foreach (self::SENSITIVE_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $ipInfo['attributes'], "Field '{$field}' must not be visible to canSeeCountry users");
        }
    }

    // -------------------------------------------------------------------------
    // showFlag + showIPCountry preference — countryCode only, no sensitive fields
    // -------------------------------------------------------------------------

    #[Test]
    public function showflag_post_author_preference_exposes_country_to_others()
    {
        $this->setting('fof-geoip.showFlag', true);

        // Post 2 is authored by user 4 who has showIPCountry=true.
        // Any authenticated viewer should see the country for that post.
        $body = $this->getPostWithIpInfo(2, 2);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNotNull($ipInfo, 'Post author showIPCountry preference should expose ip_info to viewers');
        $this->assertArrayHasKey('countryCode', $ipInfo['attributes']);
        $this->assertEquals('FR', $ipInfo['attributes']['countryCode']);
    }

    #[Test]
    public function showflag_post_author_preference_does_not_expose_sensitive_fields()
    {
        $this->setting('fof-geoip.showFlag', true);

        $body = $this->getPostWithIpInfo(2, 2);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNotNull($ipInfo);

        foreach (self::SENSITIVE_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $ipInfo['attributes'], "Field '{$field}' must not be visible via showFlag preference");
        }
    }

    #[Test]
    public function showflag_enabled_but_post_author_has_no_preference_hides_ip_info()
    {
        $this->setting('fof-geoip.showFlag', true);

        // Post 1 is authored by admin (user 1) who has no showIPCountry preference.
        // User 2 has no canSeeCountry permission either — should see nothing.
        $body = $this->getPostWithIpInfo(1, 2);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNull($ipInfo, 'ip_info should not be visible when post author has not opted in');
    }

    // -------------------------------------------------------------------------
    // viewIps user (moderator) — all fields
    // -------------------------------------------------------------------------

    #[Test]
    public function viewips_user_receives_ip_address()
    {
        $body = $this->getPostWithIpInfo(1, 5);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNotNull($ipInfo, 'viewIps user should receive ip_info');
        $this->assertArrayHasKey('ip', $ipInfo['attributes']);
        $this->assertEquals('1.2.3.4', $ipInfo['attributes']['ip']);
    }

    #[Test]
    public function viewips_user_receives_all_fields()
    {
        $body = $this->getPostWithIpInfo(1, 5);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNotNull($ipInfo);
        $attrs = $ipInfo['attributes'];

        $this->assertEquals('DE', $attrs['countryCode']);
        $this->assertEquals('1.2.3.4', $attrs['ip']);
        $this->assertEquals('10115', $attrs['zipCode']);
        $this->assertEquals('52.5200', $attrs['latitude']);
        $this->assertEquals('13.4050', $attrs['longitude']);
        $this->assertEquals('Test ISP GmbH', $attrs['isp']);
        $this->assertEquals('Test Org', $attrs['organization']);
        $this->assertEquals('AS12345 Test ISP GmbH', $attrs['as']);
    }

    // -------------------------------------------------------------------------
    // Admin — all fields
    // -------------------------------------------------------------------------

    #[Test]
    public function admin_receives_all_fields()
    {
        $body = $this->getPostWithIpInfo(1, 1);
        $ipInfo = $this->extractIpInfo($body);

        $this->assertNotNull($ipInfo);
        $this->assertArrayHasKey('ip', $ipInfo['attributes']);
        $this->assertEquals('1.2.3.4', $ipInfo['attributes']['ip']);
        $this->assertArrayHasKey('isp', $ipInfo['attributes']);
        $this->assertArrayHasKey('latitude', $ipInfo['attributes']);
    }

    // -------------------------------------------------------------------------
    // Cross-check: canSeeCountry user never escalates to full ip via direct endpoint
    // -------------------------------------------------------------------------

    #[Test]
    public function can_see_country_user_cannot_get_ip_via_direct_endpoint()
    {
        $response = $this->send(
            $this->request('GET', '/api/ip_info/'.urlencode('1.2.3.4'), ['authenticatedAs' => 3])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        $this->assertArrayNotHasKey('ip', $body['data']['attributes'], 'canSeeCountry user must not see ip via direct endpoint');

        foreach (self::SENSITIVE_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $body['data']['attributes'], "Field '{$field}' must not be visible via direct endpoint to canSeeCountry users");
        }
    }

    #[Test]
    public function regular_user_cannot_get_ip_via_direct_endpoint()
    {
        $response = $this->send(
            $this->request('GET', '/api/ip_info/'.urlencode('1.2.3.4'), ['authenticatedAs' => 2])
        );

        // ScopeIPInfoVisibility allows authenticated users to query, but fields are gated
        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        foreach (self::SENSITIVE_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $body['data']['attributes'], "Field '{$field}' must not be visible via direct endpoint to regular users");
        }
    }
}
