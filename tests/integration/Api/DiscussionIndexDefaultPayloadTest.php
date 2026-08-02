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

class DiscussionIndexDefaultPayloadTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $now = Carbon::now();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'One', 'created_at' => $now, 'last_posted_at' => $now, 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Two', 'created_at' => $now, 'last_posted_at' => $now, 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => $now, 'user_id' => 1, 'type' => 'comment', 'number' => 1, 'content' => '<t><p>first</p></t>', 'ip_address' => '10.0.0.1'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => $now, 'user_id' => 1, 'type' => 'comment', 'number' => 1, 'content' => '<t><p>second</p></t>', 'ip_address' => '10.0.0.2'],
            ],
            IPInfo::class => [
                ['address' => '10.0.0.1', 'country_code' => 'DE', 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString()],
                ['address' => '10.0.0.2', 'country_code' => 'FR', 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString()],
            ],
        ]);
    }

    protected function includedOfType(array $body, string $type): array
    {
        return array_values(array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === $type));
    }

    #[Test]
    public function the_discussions_index_serializes_no_posts_by_default(): void
    {
        // Nothing on the discussion list displays ip_info — flags render in
        // the post stream, which gets its data from the posts endpoint.
        // Default-including firstPost.ipInfo forced every first post to be
        // fully serialized (rendered HTML, per-post policies) on every index
        // view, for data no list client reads.
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(0, $this->includedOfType($body, 'posts'), 'The index must not serialize posts by default.');
        $this->assertCount(0, $this->includedOfType($body, 'ip_info'), 'The index must not serialize ip_info by default.');
    }

    #[Test]
    public function explicitly_including_first_post_ip_info_still_works(): void
    {
        // Clients that want the data (moderation tools, API consumers) can
        // still ask for it, and the batched eager loads still apply.
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams(['include' => 'firstPost,firstPost.ipInfo'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(2, $this->includedOfType($body, 'posts'));
        $this->assertCount(2, $this->includedOfType($body, 'ip_info'));
    }
}
