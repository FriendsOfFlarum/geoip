<?php

namespace FoF\GeoIP\Tests\integration\Api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ShowIPEndpontTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser()
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 4], // Make normal user a moderator
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'viewIps'], // Give moderators permission to view IP info
            ],
            ]);
    }

    public static function userIpProvider(): array
    {
        return [
            'IPv4 - Google DNS' => [1, '8.8.8.8'],
            'IPv6 - Google DNS' => [1, '2001:4860:4860::8888'],
            'IPv4 - Cloudflare DNS' => [2, '1.1.1.1'],
            'IPv6 - Cloudflare DNS' => [2, '2606:4700:4700::1111'],
        ];
    }

    #[Test]
    #[DataProvider('userIpProvider')]
    public function can_access_ip_info_endpoint(int $userId, string $ip)
    {
        $encodedIp = urlencode($ip);

        $response = $this->send(
            $this->request('GET', "/api/ip_info/{$encodedIp}", [
                'authenticatedAs' => $userId,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertEquals('ip_info', $body['data']['type']);

        // The ID is a hash of the decoded IP address (as stored in the model)
        $this->assertEquals(hash('sha256', $ip), $body['data']['id']);
        $this->assertEquals($ip, $body['data']['attributes']['ip']);
        $this->assertNotEmpty($body['data']['attributes']['countryCode']);
    }
}
