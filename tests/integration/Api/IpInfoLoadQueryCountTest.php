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
 * Regression test for the per-post ip_info load.
 *
 * The ipInfo relationships of included posts were resolved one post at a
 * time (core's deferred include resolution is depth-first), so a discussion
 * list issued one `ip_info` query per included post, and the post stream one
 * per post. Eager-loading the relation alongside the posts collapses this to
 * one query per relation path, regardless of page size.
 */
class IpInfoLoadQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** Number of discussions seeded for the list. */
    private const DISCUSSION_COUNT = 15;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $users = [$this->normalUser()];
        $discussions = [];
        $posts = [];
        $ipInfo = [];
        $now = Carbon::now();

        // Each discussion has a distinct firstPost and lastPost, each with its
        // own IP address and a stored ip_info row — so a per-post load shows
        // up as 2 × DISCUSSION_COUNT queries on the discussion list.
        for ($i = 0; $i < self::DISCUSSION_COUNT; $i++) {
            $discussionId = 100 + $i;
            $firstPostId = 1000 + $i;
            $lastPostId = 2000 + $i;
            $firstIp = "10.1.0.$i";
            $lastIp = "10.1.1.$i";

            $discussions[] = [
                'id'             => $discussionId,
                'title'          => "Discussion $discussionId",
                'slug'           => "discussion-$discussionId",
                'created_at'     => $now->toDateTimeString(),
                'last_posted_at' => $now->copy()->addSeconds($i)->toDateTimeString(),
                'user_id'        => 2,
                'first_post_id'  => $firstPostId,
                'last_post_id'   => $lastPostId,
                'comment_count'  => 2,
            ];
            $posts[] = [
                'id'            => $firstPostId,
                'discussion_id' => $discussionId,
                'number'        => 1,
                'created_at'    => $now->toDateTimeString(),
                'user_id'       => 2,
                'type'          => 'comment',
                'content'       => '<t><p>first</p></t>',
                'ip_address'    => $firstIp,
            ];
            $posts[] = [
                'id'            => $lastPostId,
                'discussion_id' => $discussionId,
                'number'        => 2,
                'created_at'    => $now->copy()->addSeconds($i)->toDateTimeString(),
                'user_id'       => 2,
                'type'          => 'comment',
                'content'       => '<t><p>last</p></t>',
                'ip_address'    => $lastIp,
            ];
            $ipInfo[] = [
                'address'      => $firstIp,
                'country_code' => 'DE',
                'created_at'   => $now->toDateTimeString(),
                'updated_at'   => $now->toDateTimeString(),
            ];
            $ipInfo[] = [
                'address'      => $lastIp,
                'country_code' => 'FR',
                'created_at'   => $now->toDateTimeString(),
                'updated_at'   => $now->toDateTimeString(),
            ];
        }

        $this->prepareDatabase([
            User::class       => $users,
            Discussion::class => $discussions,
            Post::class       => $posts,
            IPInfo::class     => $ipInfo,
        ]);
    }

    private function countIpInfoQueries(array $log): int
    {
        return count(array_filter(
            $log,
            fn (array $q) => stripos($q['query'], 'ip_info') !== false
        ));
    }

    #[Test]
    public function discussion_list_loads_ip_info_once_per_relation_path_not_per_post()
    {
        $this->app();

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        // Actor 1 (admin) passes the viewIps visibility check for every post.
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams(['include' => 'firstPost,firstPost.ipInfo,lastPost,lastPost.ipInfo'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $queries = $this->countIpInfoQueries($db->getQueryLog());
        $db->flushQueryLog();

        $body = json_decode($response->getBody()->getContents(), true);

        // Sanity: ip_info resources actually serialized for the included posts.
        $included = collect($body['included'] ?? [])->where('type', 'ip_info');
        $this->assertGreaterThanOrEqual(2 * self::DISCUSSION_COUNT, $included->count());

        // One eager load per relation path (firstPost.ip_info, lastPost.ip_info)
        // — must not scale with the number of discussions.
        $this->assertSame(
            2,
            $queries,
            "Discussion list issued $queries ip_info queries for ".self::DISCUSSION_COUNT.
            ' discussions — ip_info must be eager-loaded per relation path, not loaded once per included post.'
        );
    }

    #[Test]
    public function post_stream_loads_ip_info_once_not_per_post()
    {
        $this->app();

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['discussion' => 100]])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $queries = $this->countIpInfoQueries($db->getQueryLog());
        $db->flushQueryLog();

        $body = json_decode($response->getBody()->getContents(), true);

        // Sanity: the ipInfo include is on by default and serialized.
        $included = collect($body['included'] ?? [])->where('type', 'ip_info');
        $this->assertGreaterThanOrEqual(2, $included->count());

        $this->assertSame(
            1,
            $queries,
            "Post stream issued $queries ip_info queries — ip_info must be eager-loaded with the posts in one query."
        );
    }

    /**
     * The relationship's withDefault used to trigger lookups for missing
     * ip_info rows; that moved to serialization time, where the miss is
     * observed on the eager-loaded relation. This pins that a post with no
     * stored ip_info still gets a retrieval queued when it is serialized to
     * an actor who can see IP info.
     */
    #[Test]
    public function missing_ip_info_rows_still_queue_a_lookup_when_serialized()
    {
        $this->app();

        $missingIp = '10.1.0.0'; // firstPost of discussion 100
        $this->database()->table('ip_info')->where('address', $missingIp)->delete();

        // Prime the job's "already retrieving" cache guard so the (sync) job
        // exits before calling the external geoip service; the queue push and
        // its bookkeeping still happen.
        $this->app()->getContainer()->make('cache.store')->add("fof-geoip.retrieving.$missingIp", true, 60);

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['discussion' => 100]])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertContains(
            $missingIp,
            \FoF\GeoIP\Jobs\RetrieveIP::$queued,
            'Serializing a post with no stored ip_info must queue a lookup for its IP.'
        );
    }
}
