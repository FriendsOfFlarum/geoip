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
use FoF\GeoIP\Tests\fixtures\MmdbBuilder;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;

/**
 * Sending a private message looks its address up, as posting does, so the
 * record exists by the time anyone opens the message.
 */
class MessageIpLookupTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages', 'fof-geoip');

        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->dir = sys_get_temp_dir().'/fof-geoip-messages-'.bin2hex(random_bytes(6));
        mkdir($this->dir);

        (new MmdbBuilder('DBIP-Country-Lite'))
            ->add('1.1.1.0', 24, ['country' => ['iso_code' => 'AU']])
            ->write($this->dir.'/country.mmdb');

        $this->setting('fof-geoip.service', 'maxmind');
        $this->setting('fof-geoip.services.maxmind.country_path', $this->dir.'/country.mmdb');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 102, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 1, 'content' => 'Hello', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 1, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 2, 'joined_at' => Carbon::now()],
            ],
        ]);

        // Boots the app, so the model queries below have a connection.
        $this->app();
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/country.mmdb');
        @rmdir($this->dir);

        parent::tearDown();
    }

    #[Test]
    public function sending_a_message_resolves_the_senders_address(): void
    {
        $this->assertNull(IPInfo::query()->find('1.1.1.1'));

        // The address comes from the connection, as core's ProcessIp reads it.
        $request = new ServerRequest(['REMOTE_ADDR' => '1.1.1.1'], [], '/api/dialog-messages', 'POST');
        $request = $this->requestWithJsonBody($request, ['data' => [
            'type'          => 'dialog-messages',
            'attributes'    => ['content' => 'Hi there'],
            'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '102']]],
        ]]);
        $request = $this->requestAsUser($request, 2);

        $response = $this->send($request);

        $this->assertEquals(201, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertSame('AU', IPInfo::query()->find('1.1.1.1')?->country_code, 'sending a message should resolve its address');
    }
}
