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
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests the public, self-disclosed custom flag (customFlagCountry):
 *
 * - Feature off (default): the field is never exposed, even with a stored value.
 * - Feature on:            the field is public — visible to every actor including guests.
 * - Invalid stored codes:  sanitized to null in the payload.
 * - showIPCountry:         remains gated independently by showFlag, unaffected by this feature.
 */
class CustomFlagTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id=2, no custom flag preference
                // User 3: a regular member with a valid custom flag preference stored.
                ['id' => 3, 'username' => 'flagged', 'email' => 'flagged@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['customFlagCountry' => 'JP'])],
                // User 4: a member with an invalid custom flag preference stored.
                ['id' => 4, 'username' => 'badflag', 'email' => 'badflag@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['customFlagCountry' => 'not-a-code'])],
            ],
        ]);
    }

    private function showUser(int $id, ?int $actor): array
    {
        $response = $this->send(
            $this->request('GET', "/api/users/$id", ['authenticatedAs' => $actor])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody(), true)['data']['attributes'];
    }

    #[Test]
    public function custom_flag_is_not_exposed_when_feature_disabled_by_default()
    {
        // Feature off (default). Even though user 3 has a stored preference, it
        // must not leak into the payload.
        $attributes = $this->showUser(3, 2);

        $this->assertArrayNotHasKey('customFlagCountry', $attributes, 'Custom flag should be hidden when the admin feature is off');
    }

    #[Test]
    public function custom_flag_is_exposed_to_everyone_when_feature_enabled()
    {
        $this->setting('fof-geoip.allowCustomFlag', true);

        // Viewed by user 2, a regular member with no special permission.
        $attributes = $this->showUser(3, 2);

        $this->assertArrayHasKey('customFlagCountry', $attributes, 'Custom flag should be visible to everyone when the feature is on');
        $this->assertEquals('JP', $attributes['customFlagCountry']);
    }

    #[Test]
    public function custom_flag_is_exposed_to_guests_when_feature_enabled()
    {
        $this->setting('fof-geoip.allowCustomFlag', true);

        $attributes = $this->showUser(3, null);

        $this->assertArrayHasKey('customFlagCountry', $attributes);
        $this->assertEquals('JP', $attributes['customFlagCountry']);
    }

    #[Test]
    public function custom_flag_is_null_when_user_has_not_chosen_one()
    {
        $this->setting('fof-geoip.allowCustomFlag', true);

        // User 2 has no stored customFlagCountry preference.
        $attributes = $this->showUser(2, 2);

        $this->assertArrayHasKey('customFlagCountry', $attributes);
        $this->assertNull($attributes['customFlagCountry']);
    }

    #[Test]
    public function invalid_stored_custom_flag_is_sanitized_to_null()
    {
        $this->setting('fof-geoip.allowCustomFlag', true);

        $attributes = $this->showUser(4, 2);

        $this->assertArrayHasKey('customFlagCountry', $attributes);
        $this->assertNull($attributes['customFlagCountry'], 'An invalid stored country code should be sanitized to null');
    }

    #[Test]
    public function custom_flag_is_hidden_again_after_feature_is_disabled()
    {
        // Represents the "feature was once enabled, user picked a flag, admin then
        // disabled it" scenario: user 3 has a valid custom flag stored in the DB,
        // but the feature is OFF. The stored preference must be ignored so the
        // payload looks exactly as if the feature had never been enabled.
        $stored = json_decode(
            $this->database()->table('users')->where('id', 3)->value('preferences'),
            true
        );
        $this->assertEquals('JP', $stored['customFlagCountry'], 'Sanity check: a custom flag is still stored for this user');

        // Feature is off (default — not enabled in this test).
        $attributes = $this->showUser(3, 2);

        $this->assertArrayNotHasKey('customFlagCountry', $attributes, 'Custom flag must be hidden once the feature is disabled, even though the preference is still stored');
    }

    #[Test]
    public function precedence_both_ip_optin_and_custom_flag_are_independently_exposed()
    {
        // When both features are on and a user has the IP opt-in AND a custom
        // flag, the API exposes both attributes; the frontend decides precedence
        // (custom flag wins in the post list). This guards the data contract the
        // frontend relies on.
        $this->setting('fof-geoip.showFlag', true);
        $this->setting('fof-geoip.allowCustomFlag', true);

        $this->database()->table('users')->where('id', 3)->update(['preferences' => json_encode(['showIPCountry' => true, 'customFlagCountry' => 'JP'])]);

        $attributes = $this->showUser(3, 2);

        $this->assertTrue($attributes['showIPCountry']);
        $this->assertEquals('JP', $attributes['customFlagCountry']);
    }

    #[Test]
    public function showipcountry_is_unaffected_by_custom_flag_feature()
    {
        // The IP-based opt-in is gated by `showFlag`, independent of allowCustomFlag.
        $this->setting('fof-geoip.showFlag', true);

        $attributes = $this->showUser(2, 2);

        $this->assertArrayHasKey('showIPCountry', $attributes);
    }

    #[Test]
    public function showipcountry_is_hidden_when_showflag_off()
    {
        $attributes = $this->showUser(2, 2);

        $this->assertArrayNotHasKey('showIPCountry', $attributes);
    }
}
