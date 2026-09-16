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
use PHPUnit\Framework\Attributes\Test;

/**
 * ip_info on the audit log.
 *
 * The audit log renders an IP per row but has no ip_info of its own, so each
 * row fetched its own record after the page had painted — a request per
 * distinct address, arriving late and visibly. The relation is attached to
 * flarum/audit's model and eager loaded on its endpoint, exactly as it is for
 * posts, so one query serves the whole page and the records ship with it.
 *
 * flarum/audit is a dev dependency so this always runs; in production the
 * integration is registered behind an Extend\Conditional and is inert when
 * that extension is absent.
 */
class AuditIpInfoTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-audit', 'fof-geoip');

        AuditLogger::$testMode = true;
        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            IPInfo::class => [
                [
                    'address'      => '8.8.8.8',
                    'country_code' => 'US',
                    'city'         => 'Mountain View',
                    'isp'          => 'Google LLC',
                    'created_at'   => Carbon::now()->toDateTimeString(),
                    'updated_at'   => Carbon::now()->toDateTimeString(),
                ],
            ],
            AuditLog::class => [
                ['id' => 1, 'actor_id' => 1, 'client' => 'session', 'ip_address' => '8.8.8.8', 'action' => 'cache_cleared', 'created_at' => Carbon::now()->toDateTimeString()],
                // The same address again: the relation must serve both rows
                // from the one batched query.
                ['id' => 2, 'actor_id' => 1, 'client' => 'session', 'ip_address' => '8.8.8.8', 'action' => 'cache_cleared', 'created_at' => Carbon::now()->toDateTimeString()],
                // Rows without an IP are routine — CLI actions have none.
                ['id' => 3, 'actor_id' => null, 'client' => 'cli', 'ip_address' => null, 'action' => 'cache_cleared', 'created_at' => Carbon::now()->toDateTimeString()],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        AuditLogger::$testMode = false;

        parent::tearDown();
    }

    private function auditIndex(?int $userId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/audit', ['authenticatedAs' => $userId])
        );

        return [$response->getStatusCode(), json_decode($response->getBody(), true)];
    }

    /** Find a seeded row within the index payload. */
    private function row(array $body, string $id): ?array
    {
        foreach ($body['data'] as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    #[Test]
    public function ip_info_ships_with_the_audit_index(): void
    {
        [$status, $body] = $this->auditIndex(1);

        $this->assertEquals(200, $status);

        $included = array_filter($body['included'] ?? [], fn (array $i) => $i['type'] === 'ip_info');

        $this->assertNotEmpty($included, 'ip_info should ship with the audit payload rather than being fetched per row');
        $this->assertSame('US', array_values($included)[0]['attributes']['countryCode']);
    }

    #[Test]
    public function a_row_with_an_ip_carries_the_relationship(): void
    {
        [, $body] = $this->auditIndex(1);

        $row = $this->row($body, '1');

        $this->assertNotNull($row, 'seeded audit row not present in the index');
        $this->assertNotNull($row['relationships']['ipInfo']['data'] ?? null);
    }

    #[Test]
    public function a_row_without_an_ip_has_no_relation(): void
    {
        [, $body] = $this->auditIndex(1);

        $row = $this->row($body, '3');

        $this->assertNotNull($row);
        $this->assertNull($row['attributes']['ipAddress']);
        $this->assertNull($row['relationships']['ipInfo']['data'] ?? null);
    }

    /**
     * One record serves both rows sharing that address: the relation is loaded
     * in a single batched query rather than once per row.
     */
    #[Test]
    public function a_repeated_address_is_included_once(): void
    {
        [, $body] = $this->auditIndex(1);

        $included = array_filter(
            $body['included'] ?? [],
            fn (array $i) => $i['type'] === 'ip_info' && ($i['attributes']['ip'] ?? null) === '8.8.8.8'
        );

        $this->assertCount(1, $included);
    }

    /**
     * Every row on the index carries the relationship, so the frontend never
     * has to fetch one — the behaviour that made the audit log slow to enrich.
     */
    #[Test]
    public function every_index_row_carries_the_relationship(): void
    {
        [$status, $body] = $this->auditIndex(1);

        $this->assertEquals(200, $status);
        $this->assertNotEmpty($body['data']);

        foreach ($body['data'] as $row) {
            $this->assertArrayHasKey('ipInfo', $row['relationships'], 'audit row '.$row['id'].' is missing the ipInfo relationship');
        }
    }
}
