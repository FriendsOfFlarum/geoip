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
 * Regression test for the ip_info author N+1.
 *
 * The ipInfo field's visibility callback reads the post author's
 * showIPCountry preference ($post->user?->getPreference(...)). The
 * discussion list serializes firstPost.ipInfo for every row, so without
 * eager-loading the author this lazy-loads one `select * from users where
 * id = ?` per discussion during serialization — an N+1 that scales with the
 * page size on the hottest endpoint in the app.
 *
 * The fix (a) short-circuits the callback so $post->user is only touched on
 * the one decisive path, and (b) eager-loads firstPost.user / post.user where
 * the ipInfo include is added. This test seeds many discussions with distinct
 * authors and asserts the per-author user fetch stays bounded — under the bug
 * it grew linearly with the number of discussions.
 */
class IpInfoListQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** Number of discussions seeded for the list. */
    private const DISCUSSION_COUNT = 15;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        $users = [$this->normalUser()]; // id=2, plain viewer
        $discussions = [];
        $posts = [];
        $ipInfo = [];
        $now = Carbon::now();

        // Each discussion has TWO posts authored by TWO DISTINCT users, both
        // opted in to showIPCountry: a firstPost by author 1xx and a reply
        // (the lastPost) by author 2xx. This is deliberate — the ip_info
        // visibility callback runs for every serialized post, so the
        // discussion list evaluates it for BOTH firstPost and lastPost. A naive
        // firstPost-only fix leaves the lastPost author as an N+1; using two
        // different authors per discussion makes that leak show up as extra
        // single-row user fetches.
        for ($i = 0; $i < self::DISCUSSION_COUNT; $i++) {
            $firstAuthor = 100 + $i;
            $lastAuthor = 200 + $i;
            $discussionId = 100 + $i;
            $firstPostId = 1000 + $i;
            $lastPostId = 2000 + $i;
            $firstIp = "10.0.0.$i";
            $lastIp = "10.0.1.$i";

            foreach ([$firstAuthor, $lastAuthor] as $authorId) {
                $users[] = [
                    'id'                 => $authorId,
                    'username'           => "author$authorId",
                    'email'              => "author$authorId@example.com",
                    'is_email_confirmed' => 1,
                    'preferences'        => json_encode(['showIPCountry' => true]),
                ];
            }

            $discussions[] = [
                'id'             => $discussionId,
                'title'          => "Discussion $discussionId",
                'slug'           => "discussion-$discussionId",
                'created_at'     => $now->toDateTimeString(),
                'last_posted_at' => $now->copy()->addSeconds($i)->toDateTimeString(),
                'user_id'        => $firstAuthor,
                'first_post_id'  => $firstPostId,
                'last_post_id'   => $lastPostId,
                'comment_count'  => 2,
            ];
            $posts[] = [
                'id'            => $firstPostId,
                'discussion_id' => $discussionId,
                'number'        => 1,
                'created_at'    => $now->toDateTimeString(),
                'user_id'       => $firstAuthor,
                'type'          => 'comment',
                'content'       => '<t><p>first</p></t>',
                'ip_address'    => $firstIp,
            ];
            $posts[] = [
                'id'            => $lastPostId,
                'discussion_id' => $discussionId,
                'number'        => 2,
                'created_at'    => $now->copy()->addSeconds($i)->toDateTimeString(),
                'user_id'       => $lastAuthor,
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

    /**
     * Count `select * from users where id = ?` queries during a request.
     *
     * @return int
     */
    private function countUserByIdQueries(array $log): int
    {
        return count(array_filter(
            $log,
            fn (array $q) => preg_match('/^select \* from [`"]users[`"] where [`"]users[`"]\.[`"]id[`"] = \?/i', $q['query']) === 1
        ));
    }

    /**
     * The memoizing resolver batches author lookups, so single-row user fetches
     * must not scale with the number of discussions/posts. The bug fetched one
     * user per serialized post (firstPost AND lastPost), i.e. ~2× the discussion
     * count. This bound is a small fixed allowance for unrelated per-request
     * single-row user loads from core/other code — comfortably below even one
     * fetch per discussion, so it fails loudly if the N+1 returns for either the
     * firstPost or lastPost author.
     */
    private const MAX_SINGLE_USER_FETCHES = 5;

    /**
     * Request the list with the post relations whose ip_info is serialized.
     * Both firstPost and lastPost are included so the test exercises the
     * lastPost (reply author) path, not just firstPost.
     */
    private function listResponse(int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => $actorId])
                ->withQueryParams(['include' => 'firstPost,firstPost.ipInfo,lastPost,lastPost.ipInfo'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return [$response, $this->database()];
    }

    #[Test]
    public function listing_discussions_does_not_load_authors_one_by_one_with_showflag_on()
    {
        // showFlag on is the path whose visibility check needs the author
        // preference — for every serialized post (firstPost and lastPost).
        $this->setting('fof-geoip.showFlag', true);

        $db = $this->database();
        $db->enableQueryLog();

        $this->listResponse(2);

        $userById = $this->countUserByIdQueries($db->getQueryLog());
        $db->flushQueryLog();

        $this->assertLessThanOrEqual(
            self::MAX_SINGLE_USER_FETCHES,
            $userById,
            "Discussion list issued $userById single-row user fetches for ".self::DISCUSSION_COUNT.
            ' discussions (each with a distinct firstPost and lastPost author). This is the signature of the '.
            'ip_info author N+1 — the visibility callback is loading post authors one by one instead of via the '.
            'memoizing resolver. Note a firstPost-only fix would still leak the lastPost author here.'
        );
    }

    #[Test]
    public function listing_discussions_with_showflag_off_does_not_touch_authors_per_post()
    {
        // showFlag off: the callback short-circuits before consulting the author
        // preference, so a plain viewer's list must not load post authors at all.
        $db = $this->database();
        $db->enableQueryLog();

        $this->listResponse(2);

        $userById = $this->countUserByIdQueries($db->getQueryLog());
        $db->flushQueryLog();

        $this->assertLessThanOrEqual(
            self::MAX_SINGLE_USER_FETCHES,
            $userById,
            "Discussion list issued $userById single-row user fetches with showFlag off — the visibility callback ".
            'should short-circuit before consulting the author preference.'
        );
    }
}
