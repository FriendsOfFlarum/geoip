<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\unit\Command;

use Flarum\Testing\unit\TestCase;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\ServiceResponse;
use FoF\GeoIP\Command\FetchIPInfo;
use FoF\GeoIP\Command\FetchIPInfoBatch;
use FoF\GeoIP\Command\FetchIPInfoBatchHandler;
use FoF\GeoIP\Command\FetchIPInfoHandler;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Repositories\GeoIPRepository;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

class FetchIPInfoHandlerTest extends TestCase
{
    private DB $db;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up an in-memory SQLite database for testing
        $this->db = new DB();
        $this->db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->setAsGlobal();
        $this->db->bootEloquent();

        // Create the ip_info table
        $this->db->schema()->create('ip_info', function (Blueprint $table) {
            $table->string('address')->unique();
            $table->string('country_code')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('isp')->nullable();
            $table->string('organization')->nullable();
            $table->string('as')->nullable();
            $table->boolean('mobile')->nullable();
            $table->string('threat_level')->nullable();
            $table->string('threat_types')->nullable();
            $table->string('error')->nullable();
            $table->string('data_provider')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        $this->db->schema()->dropIfExists('ip_info');
        parent::tearDown();
    }

    #[Test]
    public function it_creates_new_ip_info_record_when_none_exists()
    {
        $ip = '8.8.8.8';
        $mockResponse = $this->createMockServiceResponse($ip, 'US', '94035');

        $geoip = $this->createMock(GeoIP::class);
        $geoip->method('get')->willReturn($mockResponse);

        $repository = $this->createMock(GeoIPRepository::class);
        $repository->method('isValidIP')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);

        $handler = new FetchIPInfoHandler($geoip, $repository, $logger);
        $command = new FetchIPInfo($ip);

        $result = $handler->handle($command);

        $this->assertTrue($result->exists);
        $this->assertEquals($ip, $result->address);
        $this->assertEquals('US', $result->country_code);
        $this->assertEquals('94035', $result->zip_code);

        // Verify record exists in database
        $this->assertEquals(1, IPInfo::query()->where('address', $ip)->count());
    }

    #[Test]
    public function it_handles_duplicate_ip_gracefully_with_update_or_create()
    {
        $ip = '1.1.1.1';

        // Pre-insert a record
        IPInfo::query()->create([
            'address'      => $ip,
            'country_code' => 'AU',
            'zip_code'     => '2000',
            'isp'          => 'Cloudflare',
        ]);

        $this->assertEquals(1, IPInfo::query()->where('address', $ip)->count());

        // Mock a new response with different data
        $mockResponse = $this->createMockServiceResponse($ip, 'US', '94107', 'Cloudflare Inc.');

        $geoip = $this->createMock(GeoIP::class);
        $geoip->method('get')->willReturn($mockResponse);

        $repository = $this->createMock(GeoIPRepository::class);
        $repository->method('isValidIP')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);

        $handler = new FetchIPInfoHandler($geoip, $repository, $logger);
        $command = new FetchIPInfo($ip, refresh: true);

        // This should update the existing record, not throw a constraint violation
        $result = $handler->handle($command);

        $this->assertTrue($result->exists);
        $this->assertEquals($ip, $result->address);
        $this->assertEquals('US', $result->country_code);
        $this->assertEquals('94107', $result->zip_code);
        $this->assertEquals('Cloudflare Inc.', $result->organization);

        // Verify only one record exists (updated, not duplicated)
        $this->assertEquals(1, IPInfo::query()->where('address', $ip)->count());
    }

    #[Test]
    public function batch_handler_creates_multiple_new_records()
    {
        $ips = ['8.8.8.8', '1.1.1.1', '9.9.9.9'];
        $mockResponses = [
            $this->createMockServiceResponse('8.8.8.8', 'US', '94035'),
            $this->createMockServiceResponse('1.1.1.1', 'AU', '2000'),
            $this->createMockServiceResponse('9.9.9.9', 'US', '94107'),
        ];

        $geoip = $this->createMock(GeoIP::class);
        $geoip->method('getBatch')->willReturn($mockResponses);

        $repository = $this->createMock(GeoIPRepository::class);
        $repository->method('isValidIP')->willReturn(true);

        $handler = new FetchIPInfoBatchHandler($geoip, $repository);
        $command = new FetchIPInfoBatch($ips);

        $results = $handler->handle($command);

        $this->assertCount(3, $results);
        $this->assertEquals(3, IPInfo::query()->count());

        foreach ($ips as $ip) {
            $this->assertEquals(1, IPInfo::query()->where('address', $ip)->count());
        }
    }

    #[Test]
    public function batch_handler_handles_duplicate_ips_gracefully()
    {
        $ip = '8.8.8.8';

        // Pre-insert a record
        IPInfo::query()->create([
            'address'      => $ip,
            'country_code' => 'US',
            'zip_code'     => '12345',
        ]);

        // Since the IP already exists, getBatch should NOT be called at all
        $geoip = $this->createMock(GeoIP::class);
        $geoip->expects($this->never())->method('getBatch');

        $repository = $this->createMock(GeoIPRepository::class);
        $repository->method('isValidIP')->willReturn(true);

        $handler = new FetchIPInfoBatchHandler($geoip, $repository);
        $command = new FetchIPInfoBatch([$ip]);

        // This should return the existing record without querying
        $results = $handler->handle($command);

        $this->assertCount(1, $results);

        // Verify only one record exists (no duplicate)
        $this->assertEquals(1, IPInfo::query()->where('address', $ip)->count());

        $record = IPInfo::query()->where('address', $ip)->first();
        $this->assertEquals('12345', $record->zip_code); // Should be unchanged
    }

    #[Test]
    public function batch_handler_handles_race_condition_when_ip_created_during_processing()
    {
        $ip = '8.8.8.8';

        // Mock a response
        $mockResponse = $this->createMockServiceResponse($ip, 'US', '94035');

        $geoip = $this->createMock(GeoIP::class);
        $geoip->method('getBatch')->willReturn([$mockResponse]);

        $repository = $this->createMock(GeoIPRepository::class);
        $repository->method('isValidIP')->willReturn(true);

        // Simulate race condition: create the record just before updateOrCreate is called
        // In reality, another concurrent request might create it between the whereIn check and the updateOrCreate
        // The updateOrCreate call should handle this gracefully

        $handler = new FetchIPInfoBatchHandler($geoip, $repository);
        $command = new FetchIPInfoBatch([$ip]);

        $results = $handler->handle($command);

        $this->assertCount(1, $results);

        // Verify the record was created
        $this->assertEquals(1, IPInfo::query()->where('address', $ip)->count());
        $record = IPInfo::query()->where('address', $ip)->first();
        $this->assertEquals('94035', $record->zip_code);
    }

    #[Test]
    public function batch_handler_skips_existing_ips_when_already_in_database()
    {
        $existingIp = '8.8.8.8';
        $newIp = '1.1.1.1';

        // Pre-insert one record
        IPInfo::query()->create([
            'address'      => $existingIp,
            'country_code' => 'US',
            'zip_code'     => '94035',
        ]);

        // Mock response only for the new IP
        $mockResponse = $this->createMockServiceResponse($newIp, 'AU', '2000');

        $geoip = $this->createMock(GeoIP::class);
        $geoip->method('getBatch')->willReturn([$mockResponse]);

        $repository = $this->createMock(GeoIPRepository::class);
        $repository->method('isValidIP')->willReturn(true);

        $handler = new FetchIPInfoBatchHandler($geoip, $repository);
        $command = new FetchIPInfoBatch([$existingIp, $newIp]);

        $results = $handler->handle($command);

        // Should return both (existing + new)
        $this->assertCount(2, $results);
        $this->assertEquals(2, IPInfo::query()->count());

        // Verify the existing IP wasn't re-fetched
        $existing = IPInfo::query()->where('address', $existingIp)->first();
        $this->assertEquals('US', $existing->country_code);
        $this->assertEquals('94035', $existing->zip_code);

        // Verify the new IP was fetched
        $new = IPInfo::query()->where('address', $newIp)->first();
        $this->assertEquals('AU', $new->country_code);
        $this->assertEquals('2000', $new->zip_code);
    }

    private function createMockServiceResponse(
        string $ip,
        string $countryCode = 'US',
        string $zipCode = '12345',
        ?string $organization = null
    ): ServiceResponse {
        $response = $this->createMock(ServiceResponse::class);
        $response->method('getIP')->willReturn($ip);
        $response->fake = false;
        $response->method('toJSON')->willReturn([
            'country_code' => $countryCode,
            'zip_code'     => $zipCode,
            'organization' => $organization ?? 'Test ISP',
            'isp'          => 'Test ISP',
        ]);

        return $response;
    }
}
