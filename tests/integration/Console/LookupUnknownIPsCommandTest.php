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
}
