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
use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Tests\fixtures\MmdbBuilder;
use Illuminate\Contracts\Queue\Queue;
use PHPUnit\Framework\Attributes\Test;

/**
 * Populating ip_info for audit entries.
 *
 * #121 taught the console command to scan the audit table, but that only helps
 * when someone runs it. These are the two paths that keep the data current on
 * their own: a listener on newly written entries, and a self-heal on the index
 * for entries that predate it.
 */
class AuditIpLookupTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-audit', 'fof-geoip');

        AuditLogger::$testMode = true;
        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->dir = sys_get_temp_dir().'/fof-geoip-audit-'.bin2hex(random_bytes(6));
        mkdir($this->dir);

        (new MmdbBuilder('DBIP-Country-Lite'))
            ->add('8.8.8.0', 24, ['country' => ['iso_code' => 'US']])
            ->add('1.1.1.0', 24, ['country' => ['iso_code' => 'AU']])
            ->write($this->dir.'/country.mmdb');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/country.mmdb');
        @rmdir($this->dir);

        AuditLogger::$testMode = false;

        parent::tearDown();
    }

    private function useOfflineDriver(): void
    {
        $this->setting('fof-geoip.service', 'maxmind');
        $this->setting('fof-geoip.services.maxmind.country_path', $this->dir.'/country.mmdb');
    }

    /**
     * A new audit entry has its address resolved as it is written, so the row
     * is complete without anyone running the console command.
     */
    #[Test]
    public function a_new_audit_entry_has_its_address_looked_up(): void
    {
        $this->useOfflineDriver();
        $this->app();

        $this->assertNull(IPInfo::query()->find('1.1.1.1'));

        AuditLogger::$ipAddress = '1.1.1.1';
        AuditLogger::log('user_logged_in');

        $this->assertSame(
            'AU',
            IPInfo::query()->find('1.1.1.1')?->country_code,
            'writing an audit entry should resolve its address'
        );
    }

    /**
     * An entry without an address is routine — CLI actions have none — and
     * must not produce a record or an error.
     */
    #[Test]
    public function an_entry_without_an_address_is_ignored(): void
    {
        $this->useOfflineDriver();
        $this->app();

        $before = IPInfo::query()->count();

        AuditLogger::$ipAddress = null;
        AuditLogger::log('cache_cleared');

        $this->assertSame($before, IPInfo::query()->count());
    }

    /**
     * Entries written before the listener existed still show a bare IP. The
     * index resolves them on the way past, as the post resource already does.
     */
    #[Test]
    public function the_index_heals_an_entry_that_predates_the_listener(): void
    {
        $this->useOfflineDriver();

        $this->prepareDatabase([
            AuditLog::class => [
                ['id' => 1, 'actor_id' => 1, 'client' => 'session', 'ip_address' => '8.8.8.8', 'action' => 'cache_cleared', 'created_at' => Carbon::now()->toDateTimeString()],
            ],
        ]);

        $response = $this->send($this->request('GET', '/api/audit', ['authenticatedAs' => 1]));

        $this->assertEquals(200, $response->getStatusCode());

        // The row was seeded directly, so no listener ran for it: only the
        // index request could have produced this record.
        $this->assertSame(
            'US',
            IPInfo::query()->find('8.8.8.8')?->country_code,
            'viewing the audit index should resolve an address that has no record'
        );
    }

    /**
     * The healed record ships in the same response, rather than appearing only
     * on the next page load.
     */
    #[Test]
    public function the_healed_record_ships_with_that_same_response(): void
    {
        $this->useOfflineDriver();

        $this->prepareDatabase([
            AuditLog::class => [
                ['id' => 1, 'actor_id' => 1, 'client' => 'session', 'ip_address' => '8.8.8.8', 'action' => 'cache_cleared', 'created_at' => Carbon::now()->toDateTimeString()],
            ],
        ]);

        $response = $this->send($this->request('GET', '/api/audit', ['authenticatedAs' => 1]));
        $body = json_decode($response->getBody(), true);

        $included = array_filter($body['included'] ?? [], fn (array $i) => $i['type'] === 'ip_info');

        $this->assertNotEmpty($included, 'the record resolved during the request should be included in it');
        $this->assertSame('US', array_values($included)[0]['attributes']['countryCode']);
    }

    /**
     * A hosted provider must not be called during the request. The address is
     * queued instead, so an audit page listing many distinct addresses cannot
     * turn into a burst of blocking HTTP round trips.
     */
    #[Test]
    public function a_hosted_provider_queues_rather_than_blocking(): void
    {
        $this->setting('fof-geoip.service', 'ipapi');
        $this->app();

        $spy = new RecordingAuditQueue();
        $this->app()->getContainer()->instance(Queue::class, $spy);
        $this->app()->getContainer()->instance('queue', $spy);

        (new \FoF\GeoIP\Listeners\RetrieveAuditIP(
            new \FoF\GeoIP\Listeners\RetrieveIP(
                $spy,
                $this->app()->getContainer()->make(\FoF\GeoIP\Repositories\GeoIPRepository::class),
                $this->app()->getContainer()->make(GeoIP::class)
            )
        ))->handle(tap(new AuditLog(), function (AuditLog $log) {
            $log->ip_address = '1.1.1.1';
        }));

        $this->assertCount(1, $spy->pushed, 'a hosted lookup should be queued, not performed inline');
        $this->assertNull(IPInfo::query()->find('1.1.1.1'));
    }

    /**
     * Healing sits behind the permission check, so a request that may not see
     * IPs does not trigger lookups.
     */
    #[Test]
    public function an_actor_without_permission_does_not_trigger_a_lookup(): void
    {
        $this->useOfflineDriver();

        $this->prepareDatabase([
            AuditLog::class => [
                ['id' => 1, 'actor_id' => 1, 'client' => 'session', 'ip_address' => '8.8.8.8', 'action' => 'cache_cleared', 'created_at' => Carbon::now()->toDateTimeString()],
            ],
        ]);

        // A normal user cannot reach the audit log at all.
        $this->send($this->request('GET', '/api/audit', ['authenticatedAs' => 2]));

        $this->assertNull(
            IPInfo::query()->find('8.8.8.8'),
            'a request that cannot see IPs must not cause a lookup'
        );
    }
}

/**
 * Records pushes instead of running them, so "queued" is distinguishable from
 * "ran inline on the sync driver" — identical from the outside otherwise.
 */
class RecordingAuditQueue extends \Illuminate\Queue\NullQueue
{
    public array $pushed = [];

    public function push($job, $data = '', $queue = null)
    {
        $this->pushed[] = $job;

        return null;
    }
}
