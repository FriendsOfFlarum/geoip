<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\integration\Console;

use Carbon\Carbon;
use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use Flarum\Discussion\Discussion;
use Flarum\Http\AccessToken;
use Flarum\Post\Post;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\User\User;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Tests\fixtures\MmdbBuilder;
use PHPUnit\Framework\Attributes\Test;

class LookupUnknownIPsCommandTest extends ConsoleTestCase
{
    use RetrievesAuthorizedUsers;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->dir = sys_get_temp_dir().'/fof-geoip-cmd-'.bin2hex(random_bytes(6));
        mkdir($this->dir);

        (new MmdbBuilder('DBIP-Country-Lite'))
            ->add('8.8.8.0', 24, ['country' => ['iso_code' => 'US']])
            ->add('1.1.1.0', 24, ['country' => ['iso_code' => 'AU']])
            ->add('8.8.4.0', 24, ['country' => ['iso_code' => 'US']])
            ->write($this->dir.'/country.mmdb');

        $this->prepareDatabase([
            User::class       => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Lookup', 'slug' => 'lookup', 'created_at' => Carbon::now()->toDateTimeString(), 'last_posted_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>a</p></t>', 'ip_address' => '8.8.8.8'],
                // A post with no recorded IP, as older or imported posts have.
                ['id' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>b</p></t>', 'ip_address' => null],
            ],
            AccessToken::class => [
                ['id' => 1, 'token' => 'a', 'user_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'last_activity_at' => Carbon::now()->toDateTimeString(), 'type' => 'session', 'last_ip_address' => '1.1.1.1'],
                // Tokens frequently have no IP recorded at all.
                ['id' => 2, 'token' => 'b', 'user_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'last_activity_at' => Carbon::now()->toDateTimeString(), 'type' => 'session', 'last_ip_address' => null],
            ],
        ]);

        $this->setting('fof-geoip.service', 'maxmind');
        $this->setting('fof-geoip.services.maxmind.country_path', $this->dir.'/country.mmdb');
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/country.mmdb');
        @rmdir($this->dir);

        // Static, so it would otherwise stay set for every later test in the
        // process once one test has enabled it.
        AuditLogger::$testMode = false;

        parent::tearDown();
    }

    /**
     * `--force` skips the "only addresses we have not seen" filter so every
     * record is looked up again. It skipped the NULL check with it, so the
     * first row without an IP — and access tokens routinely have none —
     * reached FetchIPInfo with null and killed the whole run.
     */
    #[Test]
    public function force_skips_records_without_an_ip_address(): void
    {
        $output = $this->runCommand(['command' => 'fof:geoip:lookup', '--force' => true]);

        $this->assertStringNotContainsString('must be of type string, null given', $output);

        // Both addresses that do exist were resolved.
        $this->assertNotNull(IPInfo::find('8.8.8.8'));
        $this->assertNotNull(IPInfo::find('1.1.1.1'));

        // And nothing was written for the rows that had no address.
        $this->assertNull(IPInfo::whereNull('address')->first());
        $this->assertSame(2, IPInfo::count());
    }

    /**
     * The command looks up each distinct address once, not once per row that
     * happens to carry it.
     *
     * It expressed that by selecting `id` while grouping by the address, which
     * MySQL and SQLite tolerate but PostgreSQL rejects outright: a selected
     * column must appear in GROUP BY or an aggregate. Only the address is ever
     * read from the result, so selecting it alone and taking distinct values
     * is both correct and portable.
     */
    #[Test]
    public function a_repeated_address_is_looked_up_once(): void
    {
        // Posts 3-5 share 8.8.8.8 with post 1 (see setUp), so four rows carry
        // the same address.
        $this->prepareDatabase([
            Post::class => [
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>dup</p></t>', 'ip_address' => '8.8.8.8'],
                ['id' => 4, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>dup</p></t>', 'ip_address' => '8.8.8.8'],
                ['id' => 5, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>dup</p></t>', 'ip_address' => '8.8.8.8'],
            ],
        ]);

        $this->runCommand(['command' => 'fof:geoip:lookup', '--force' => true]);

        $this->assertSame(1, IPInfo::query()->where('address', '8.8.8.8')->count());
        // 8.8.8.8 and the access token's 1.1.1.1, and nothing else.
        $this->assertSame(2, IPInfo::query()->count());
    }

    #[Test]
    public function force_refreshes_an_existing_record(): void
    {
        // Seeded through the app so the model has a booted connection.
        $this->app();
        IPInfo::query()->create([
            'address'       => '8.8.8.8',
            'country_code'  => 'ZZ',
            'data_provider' => 'stale-provider',
        ]);

        $this->runCommand(['command' => 'fof:geoip:lookup', '--force' => true]);

        $record = IPInfo::find('8.8.8.8');

        $this->assertSame('US', $record->country_code);
        $this->assertSame('DBIP-Country-Lite', $record->data_provider);
    }

    #[Test]
    public function without_force_existing_records_are_left_alone(): void
    {
        // Seeded through the app so the model has a booted connection.
        $this->app();
        IPInfo::query()->create([
            'address'       => '8.8.8.8',
            'country_code'  => 'ZZ',
            'data_provider' => 'stale-provider',
        ]);

        $this->runCommand(['command' => 'fof:geoip:lookup']);

        // Untouched, because it had already been looked up.
        $this->assertSame('ZZ', IPInfo::find('8.8.8.8')->country_code);
        // The address that had never been seen was resolved.
        $this->assertSame('AU', IPInfo::find('1.1.1.1')?->country_code);
    }

    /**
     * The per-address `info()` calls were written into the progress bar's own
     * line, which Symfony redraws in place — so the bar jumped, duplicated and
     * left fragments behind. They belong behind -v, where someone debugging a
     * specific address can ask for them.
     */
    #[Test]
    public function per_address_chatter_is_not_printed_by_default(): void
    {
        $output = $this->runCommand(['command' => 'fof:geoip:lookup']);

        $this->assertStringNotContainsString('8.8.8.8', $output);
        $this->assertStringNotContainsString('1.1.1.1', $output);
    }

    #[Test]
    public function per_address_detail_is_available_when_verbose(): void
    {
        $output = $this->runCommand([
            'command'   => 'fof:geoip:lookup',
            '--verbose' => true,
        ]);

        $this->assertStringContainsString('8.8.8.8', $output);
    }

    /**
     * A run that says nothing about what it did is not much use: the summary
     * is the part an operator actually reads.
     */
    #[Test]
    public function it_reports_what_it_resolved(): void
    {
        $output = $this->runCommand(['command' => 'fof:geoip:lookup']);

        // Two addresses exist in the seeded data and both resolve.
        $this->assertMatchesRegularExpression('/\b2\b.*resolved/i', $output);
    }

    /**
     * Addresses the databases do not cover are a normal outcome, not a
     * failure, but the operator should be told how many there were.
     */
    #[Test]
    public function it_reports_addresses_it_could_not_resolve(): void
    {
        $this->prepareDatabase([
            Post::class => [
                // 203.0.113.0/24 is the documentation range: absent from every
                // database, so it cannot resolve.
                ['id' => 20, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>x</p></t>', 'ip_address' => '203.0.113.7'],
            ],
        ]);

        $output = $this->runCommand(['command' => 'fof:geoip:lookup']);

        $this->assertMatchesRegularExpression('/unresolved|not found|could not/i', $output);
    }

    /**
     * The audit log records failed logins and blocked registrations — the
     * addresses a moderator most wants geolocated, and ones that never produce
     * a post. They were not scanned at all.
     */
    #[Test]
    public function it_looks_up_addresses_from_the_audit_log(): void
    {
        AuditLogger::$testMode = true;

        $this->extension('flarum-audit', 'fof-geoip');

        $this->prepareDatabase([
            AuditLog::class => [
                // 8.8.4.4 appears ONLY in the audit log — no post, no token —
                // so resolving it proves the audit table was scanned rather
                // than the address having been picked up elsewhere.
                ['id' => 1, 'actor_id' => 1, 'client' => 'session', 'ip_address' => '8.8.4.4', 'action' => 'user.logged_in', 'created_at' => Carbon::now()->toDateTimeString()],
                // No address: CLI actions routinely have none.
                ['id' => 2, 'actor_id' => null, 'client' => 'cli', 'ip_address' => null, 'action' => 'cache_cleared', 'created_at' => Carbon::now()->toDateTimeString()],
            ],
        ]);

        $this->runCommand(['command' => 'fof:geoip:lookup']);

        // Not referenced by any post or access token, so only the audit scan
        // could have resolved it.
        $this->assertSame(0, Post::query()->where('ip_address', '8.8.4.4')->count());
        $this->assertSame(0, AccessToken::query()->where('last_ip_address', '8.8.4.4')->count());

        $this->assertNotNull(
            IPInfo::query()->find('8.8.4.4'),
            'an address seen only in the audit log should still be resolved'
        );
    }

    /**
     * The audit table is only scanned when that extension is enabled — the
     * same guard the Draft model already gets, since the class can exist
     * while its migrations have never run.
     */
    #[Test]
    public function the_audit_table_is_skipped_when_the_extension_is_absent(): void
    {
        // flarum-audit is not enabled in this test, so the table does not
        // exist. The command must complete rather than error.
        $output = $this->runCommand(['command' => 'fof:geoip:lookup']);

        $this->assertStringNotContainsString('no such table', strtolower($output));
        $this->assertStringNotContainsString('does not exist', strtolower($output));
    }
}
