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

class NullIPTest extends TestCase
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
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'ip_address' => null],
            ],
        ]);
    }

    public function userTypes(): array
    {
        return [
            [null],
            [1],
            [2],
        ];
    }

    /**
     * @test
     *
     * @dataProvider userTypes
     */
    public function can_show_discussion_with_null_ip(?int $userId)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => $userId,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);

        $included = $data['included'];

        $posts = array_values(array_filter($included, function ($item) {
            return $item['type'] === 'posts';
        }));

        $firstPost = $posts[0];

        $this->assertEquals('posts', $firstPost['type']);
        $this->assertEquals('<p>foo bar</p>', $firstPost['attributes']['contentHtml']);

        // In this test scenario, only user Id 1 (admin) should be able to see the IP address
        if ($userId === 1) {
            $this->assertArrayHasKey('ipAddress', $firstPost['attributes'], 'IP address should be visible');
            $this->assertNull($firstPost['attributes']['ipAddress']);
        } else {
            $this->assertArrayNotHasKey('ipAddress', $firstPost['attributes'], 'IP address should not be visible');
        }
    }

    /**
     * @test
     */
    public function can_edit_post_with_null_ip()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 1,
                'json'            => [
                    'data' => [
                        'type'       => 'posts',
                        'id'         => '1',
                        'attributes' => [
                            'content' => 'foo bar - edited',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);

        $this->assertEquals('posts', $data['data']['type']);
        $this->assertEquals('foo bar - edited', $data['data']['attributes']['contentHtml']);
        $this->assertArrayHasKey('ipAddress', $data['data']['attributes']);
        $this->assertNull($data['data']['attributes']['ipAddress']);
    }
}
