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
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Tests\fixtures\MmdbBuilder;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\NullQueue;
use PHPUnit\Framework\Attributes\Test;

/**
 * Offline lookups resolve inline rather than through the queue.
 *
 * A lookup against a local database is a memory-mapped file read — measured at
 * 13µs with the C extension and 64µs in pure PHP, against roughly 400µs for a
 * single database query. Dispatching a job to perform one costs orders of
 * magnitude more than the work itself, and on a real queue driver it also
 * delays the result by however long the worker takes to pick it up.
 *
 * So when the configured service is offline, the lookup happens synchronously
 * and the record is available immediately. Hosted providers keep queueing:
 * an HTTP round trip is exactly the kind of work that should not block a
 * request.
 */
class InlineOfflineLookupTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->dir = sys_get_temp_dir().'/fof-geoip-inline-'.bin2hex(random_bytes(6));
        mkdir($this->dir);

        (new MmdbBuilder('DBIP-Country-Lite'))
            ->add('8.8.8.0', 24, ['country' => ['iso_code' => 'US']])
            ->write($this->dir.'/country.mmdb');

        $this->prepareDatabase([
            User::class        => [$this->normalUser()],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.viewIpsPosts'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 4],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Inline', 'slug' => 'inline', 'created_at' => Carbon::now()->toDateTimeString(), 'last_posted_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>a</p></t>', 'ip_address' => '8.8.8.8'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/country.mmdb');
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function useOfflineDriver(): void
    {
        $this->setting('fof-geoip.service', 'maxmind');
        $this->setting('fof-geoip.services.maxmind.country_path', $this->dir.'/country.mmdb');
    }

    /**
     * Replace the queue with one that records pushes instead of running them,
     * so a test can tell "resolved inline" from "quietly ran on the sync
     * driver", which look identical from the outside.
     */
    private function spyOnQueue(): RecordingQueue
    {
        $spy = new RecordingQueue();

        $this->app()->getContainer()->instance(Queue::class, $spy);
        $this->app()->getContainer()->instance('queue', $spy);

        return $spy;
    }

    /**
     * Build a repository wired to the given queue.
     *
     * Not resolved from the container: both the repository and the event
     * subscriber are constructed at boot, before a test can replace the queue
     * binding, so a container-resolved instance holds the real queue and the
     * spy never sees anything.
     */
    private function repository(RecordingQueue $queue): \FoF\GeoIP\Repositories\GeoIPRepository
    {
        return new \FoF\GeoIP\Repositories\GeoIPRepository(
            $this->app()->getContainer()->make(GeoIP::class),
            $queue,
            $this->app()->getContainer()->make(\Illuminate\Contracts\Bus\Dispatcher::class)
        );
    }

    #[Test]
    public function an_offline_lookup_is_resolved_without_a_job(): void
    {
        $this->useOfflineDriver();

        $spy = $this->spyOnQueue();

        $record = $this->repository($spy)->lookupForPost(Post::query()->find(1));

        $this->assertNotNull($record, 'the record should be resolved inline');
        $this->assertSame('US', $record->country_code);
        $this->assertSame([], $spy->pushed, 'no job should be queued for an offline lookup');
    }

    #[Test]
    public function the_record_is_persisted_so_it_is_not_looked_up_again(): void
    {
        $this->useOfflineDriver();

        $this->repository($this->spyOnQueue())->lookupForPost(Post::query()->find(1));

        $stored = IPInfo::query()->find('8.8.8.8');

        $this->assertNotNull($stored);
        $this->assertSame('US', $stored->country_code);
    }

    /**
     * An HTTP lookup is exactly the kind of work that should not block a
     * request, so those keep going through the queue.
     */
    #[Test]
    public function a_hosted_provider_still_queues(): void
    {
        $this->setting('fof-geoip.service', 'ipapi');

        $spy = $this->spyOnQueue();

        $this->repository($spy)->lookupForPost(Post::query()->find(1));

        $this->assertCount(1, $spy->pushed, 'a hosted provider should still queue the lookup');
    }

    /**
     * The inline path must never fire for a hosted provider: an HTTP round
     * trip during a post save would block the request on a third party.
     *
     * Asserted on the record that results, rather than on the queue: the
     * event subscriber is constructed at boot, before a test can replace the
     * queue binding, so it holds the real queue. On the sync driver that runs
     * the job immediately — which is the queue working as configured, not the
     * inline path. The two are distinguishable by which service produced the
     * record.
     */
    #[Test]
    public function a_hosted_provider_is_never_resolved_inline(): void
    {
        $this->setting('fof-geoip.service', 'ipapi');

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json'            => [
                    'data' => [
                        'attributes'    => ['content' => 'queued please'],
                        'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $record = IPInfo::query()->find(Post::query()->latest('id')->first()->ip_address);

        // Whatever wrote this, it went through the configured HTTP service —
        // not the offline driver, which would have stamped a DBIP-* type.
        if ($record !== null) {
            $this->assertStringNotContainsString('DBIP', (string) $record->data_provider);
            $this->assertStringNotContainsString('GeoLite', (string) $record->data_provider);
        }

        // And the repository refuses to resolve one inline when asked.
        // Built after the spy is installed, so it receives the spy rather than
        // the queue it was constructed with at boot.
        $spy = $this->spyOnQueue();

        IPInfo::query()->where('address', '8.8.8.8')->delete();
        $this->repository($spy)->lookupForPost(Post::query()->find(1));

        $this->assertCount(1, $spy->pushed, 'a hosted provider must queue rather than resolve inline');
        $this->assertNull(IPInfo::query()->find('8.8.8.8'), 'nothing should be written during the request');
    }

    /**
     * Saving a post resolves the address there and then, so the flag is
     * present on the very first render rather than appearing once a worker
     * has caught up.
     */
    #[Test]
    public function saving_a_post_resolves_its_address_inline(): void
    {
        $this->useOfflineDriver();

        $spy = $this->spyOnQueue();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json'            => [
                    'data' => [
                        'attributes'    => ['content' => 'inline lookup please'],
                        'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        // The test request comes from 127.0.0.1, which the databases do not
        // cover — but it still resolves, to a record describing it as a
        // reserved range, so the address is not looked up again on every
        // subsequent render.
        $created = Post::query()->latest('id')->first();

        $this->assertSame([], $spy->pushed, 'saving a post should not queue an offline lookup');
        $this->assertNotNull(
            IPInfo::query()->find($created->ip_address),
            'the address should have been resolved during the request'
        );
    }

    /**
     * The end-to-end promise: by the time the POST /api/posts response is
     * serialized, the record exists and ships with it. A moderator sees the
     * full ip_info relation on the very response that created the post, with
     * no follow-up request and no wait for a worker.
     */
    #[Test]
    public function the_creating_response_already_carries_the_ip_info(): void
    {
        $this->useOfflineDriver();
        $spy = $this->spyOnQueue();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json'            => [
                    'data' => [
                        'attributes'    => ['content' => 'inline lookup please'],
                        'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $body = json_decode($response->getBody(), true);

        // The relation is on the created post...
        $this->assertArrayHasKey('ipInfo', $body['data']['relationships'] ?? []);
        $this->assertNotNull($body['data']['relationships']['ipInfo']['data'] ?? null);

        // ...and the record itself is included in the same payload.
        $included = array_filter($body['included'] ?? [], fn (array $i) => $i['type'] === 'ip_info');
        $this->assertNotEmpty($included, 'ip_info should ship with the response that created the post');

        // No job, and the row is already persisted.
        $this->assertSame([], $spy->pushed);
        $this->assertNotNull(IPInfo::query()->find(Post::query()->latest('id')->first()->ip_address));
    }

    /**
     * The whole justification for resolving inline is that the work is
     * trivially cheap. If a page of posts cost more than a handful of
     * milliseconds of lookups, queueing would be the better trade — so the
     * cost is asserted rather than assumed.
     *
     * The bound is deliberately loose (CI runners are slow and shared); it is
     * there to catch an order-of-magnitude regression, such as the driver
     * being reconstructed per lookup, not to measure microseconds.
     */
    #[Test]
    public function resolving_a_page_of_addresses_is_cheap(): void
    {
        $this->useOfflineDriver();
        $this->spyOnQueue();

        $geoip = $this->app()->getContainer()->make(GeoIP::class);

        // 50 distinct addresses, more than any realistic page of posts.
        $ips = [];
        for ($i = 1; $i <= 50; $i++) {
            $ips[] = "8.8.8.$i";
        }

        $start = microtime(true);

        foreach ($ips as $ip) {
            $geoip->get($ip);
        }

        $elapsedMs = (microtime(true) - $start) * 1000;

        $this->assertLessThan(
            100,
            $elapsedMs,
            sprintf('50 offline lookups took %.1fms; expected well under 100ms', $elapsedMs)
        );
    }

    /**
     * An unusable offline driver must not be retried per address: it fails
     * identically for every one of them.
     */
    #[Test]
    public function an_unavailable_offline_driver_neither_queues_nor_writes(): void
    {
        $this->setting('fof-geoip.service', 'maxmind');

        $spy = $this->spyOnQueue();

        $record = $this->repository($spy)->lookupForPost(Post::query()->find(1));

        $this->assertNull($record);
        $this->assertSame([], $spy->pushed);
        $this->assertNull(IPInfo::query()->find('8.8.8.8'));
    }
}

/**
 * A queue that records what it was asked to run, rather than running it.
 *
 * Extends the framework's NullQueue rather than implementing the contract by
 * hand, so it cannot fall behind when methods are added to the interface.
 */
class RecordingQueue extends NullQueue
{
    public array $pushed = [];

    public function push($job, $data = '', $queue = null): mixed
    {
        $this->pushed[] = $job;

        return null;
    }

    public function pushRaw($payload, $queue = null, array $options = []): mixed
    {
        $this->pushed[] = $payload;

        return null;
    }

    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        return $this->push($job, $data, $queue);
    }
}
