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
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Model\IPInfo;
use PHPUnit\Framework\Attributes\Test;

/**
 * A private message's address ships placed, to those who may see it, the way
 * a post's does: the relation is batched with the list, not fetched per row.
 */
class MessageIpInfoTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages', 'fof-geoip');

        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                // May see countries, nothing more.
                ['id' => 3, 'username' => 'countryviewer', 'email' => 'country@machine.local', 'is_email_confirmed' => 1],
                // Has opted in to showing where they write from.
                ['id' => 4, 'username' => 'opted', 'email' => 'opted@machine.local', 'is_email_confirmed' => 1, 'preferences' => json_encode(['showIPCountry' => true])],
            ],
            'groups' => [
                ['id' => 10, 'name_singular' => 'Country viewer', 'name_plural' => 'Country viewers'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
            ],
            'group_permission' => [
                ['group_id' => 10, 'permission' => 'fof-geoip.canSeeCountry'],
            ],
            IPInfo::class => [
                [
                    'address'      => '8.8.8.8',
                    'country_code' => 'US',
                    'city'         => 'Mountain View',
                    'isp'          => 'Google LLC',
                    'created_at'   => Carbon::now()->toDateTimeString(),
                    'updated_at'   => Carbon::now()->toDateTimeString(),
                ],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 103, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 2, 'content' => 'Hello', 'number' => 1, 'ip_address' => '8.8.8.8', 'created_at' => Carbon::now()],
                // Sent from a client that recorded no address.
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 1, 'content' => 'Hi', 'number' => 2, 'ip_address' => null, 'created_at' => Carbon::now()],
                // From a sender who opted in to showing their country.
                ['id' => 104, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hey', 'number' => 3, 'ip_address' => '8.8.8.8', 'created_at' => Carbon::now()],
            ],
            // Seeded as a group conversation so every viewer under test is a member.
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 1, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 2, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    private function messages(int $userId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => $userId])
                ->withQueryParams(['filter' => ['dialog' => '102']])
        );

        return [$response->getStatusCode(), json_decode($response->getBody(), true)];
    }

    private function row(array $body, string $id): ?array
    {
        foreach ($body['data'] as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    #[Test]
    public function ip_info_ships_with_the_messages_for_someone_who_may_see_addresses(): void
    {
        [$status, $body] = $this->messages(1);

        $this->assertEquals(200, $status);

        $included = array_filter($body['included'] ?? [], fn (array $i) => $i['type'] === 'ip_info');

        $this->assertNotEmpty($included, 'ip_info should ship with the messages rather than being fetched per row');
        $this->assertSame('US', array_values($included)[0]['attributes']['countryCode']);
        $this->assertNotNull($this->row($body, '102')['relationships']['ipInfo']['data'] ?? null);
    }

    private function ipInfoFor(array $body, string $messageId): ?array
    {
        $id = $this->row($body, $messageId)['relationships']['ipInfo']['data']['id'] ?? null;

        foreach ($body['included'] ?? [] as $included) {
            if ($included['type'] === 'ip_info' && $included['id'] === $id) {
                return $included['attributes'];
            }
        }

        return null;
    }

    #[Test]
    public function with_flags_off_a_member_without_permission_gets_nothing(): void
    {
        $this->setting('fof-geoip.showFlag', false);

        [$status, $body] = $this->messages(2);

        $this->assertEquals(200, $status);
        $this->assertArrayNotHasKey('ipInfo', $this->row($body, '102')['relationships'] ?? []);
        $this->assertArrayNotHasKey('ipInfo', $this->row($body, '104')['relationships'] ?? []);
        $this->assertEmpty(array_filter($body['included'] ?? [], fn (array $i) => $i['type'] === 'ip_info'));
    }

    #[Test]
    public function with_flags_on_a_member_sees_the_country_of_a_sender_who_opted_in_and_nothing_of_one_who_did_not(): void
    {
        $this->setting('fof-geoip.showFlag', true);

        [, $body] = $this->messages(2);

        $optedIn = $this->ipInfoFor($body, '104');
        $this->assertSame('US', $optedIn['countryCode'] ?? null);
        $this->assertArrayNotHasKey('ip', $optedIn, 'the country, not the address');

        $this->assertNull($this->ipInfoFor($body, '102'), 'a sender who has not opted in shows no flag');
    }

    #[Test]
    public function someone_who_may_see_countries_sees_them_for_every_sender_but_not_addresses(): void
    {
        $this->setting('fof-geoip.showFlag', false);

        [, $body] = $this->messages(3);

        $this->assertSame('US', $this->ipInfoFor($body, '102')['countryCode'] ?? null);
        $this->assertArrayNotHasKey('ip', $this->ipInfoFor($body, '102'));
    }

    #[Test]
    public function someone_who_may_see_addresses_sees_everything(): void
    {
        [, $body] = $this->messages(1);

        $this->assertSame('8.8.8.8', $this->ipInfoFor($body, '102')['ip'] ?? null);
    }

    #[Test]
    public function a_message_without_an_address_has_no_relation(): void
    {
        [, $body] = $this->messages(1);

        $this->assertNull($this->row($body, '103')['relationships']['ipInfo']['data'] ?? null);
    }
}
