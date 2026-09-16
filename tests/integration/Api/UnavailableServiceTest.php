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

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Model\IPInfo;
use PHPUnit\Framework\Attributes\Test;

/**
 * Selecting the offline driver without configuring any database is the most
 * likely misconfiguration, and it used to fail quietly: every lookup missed,
 * wrote no record, and logged an error — so the same address was retried on
 * every render, forever, while the log filled up.
 *
 * An unavailable service is a global, static condition rather than a per-
 * address failure, so a lookup against one should not be attempted at all.
 */
class UnavailableServiceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-geoip');

        GeoIP::$forced = null;
        GeoIP::$configured = [];

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);

        // The offline driver selected, but no database paths set at all.
        $this->setting('fof-geoip.service', 'maxmind');
    }

    private function geoip(): GeoIP
    {
        return $this->app()->getContainer()->make(GeoIP::class);
    }

    #[Test]
    public function the_service_reports_itself_unavailable(): void
    {
        $this->assertFalse($this->geoip()->isAvailable());
    }

    /**
     * A configured HTTP service has nothing to report here: availability is
     * only knowable for services that can inspect their own local state.
     */
    #[Test]
    public function an_http_service_is_assumed_available(): void
    {
        $this->setting('fof-geoip.service', 'ipapi');

        $this->assertTrue($this->geoip()->isAvailable());
    }

    #[Test]
    public function a_lookup_against_an_unavailable_service_is_not_attempted(): void
    {
        $this->assertNull($this->geoip()->get('8.8.8.8'));
    }

    /**
     * The important part: nothing is written, so the address is not marked as
     * looked-up, but equally the handler must not log a per-address error for
     * a condition that has nothing to do with the address.
     */
    #[Test]
    public function no_record_is_written_when_the_service_is_unavailable(): void
    {
        $bus = $this->app()->getContainer()->make(\Illuminate\Contracts\Bus\Dispatcher::class);

        $bus->dispatch(new \FoF\GeoIP\Command\FetchIPInfo('8.8.8.8'));

        $this->assertNull(IPInfo::query()->find('8.8.8.8'));
    }

    #[Test]
    public function the_handler_reports_the_service_is_unavailable_rather_than_the_lookup_failing(): void
    {
        $logger = new CollectingLogger();
        $this->app()->getContainer()->instance(\Psr\Log\LoggerInterface::class, $logger);

        $handler = $this->app()->getContainer()->make(\FoF\GeoIP\Command\FetchIPInfoHandler::class);
        $handler->handle(new \FoF\GeoIP\Command\FetchIPInfo('8.8.8.8'));

        $messages = implode("\n", $logger->messages);

        // One clear statement about configuration, not "could not fetch 8.8.8.8".
        $this->assertStringContainsString('not available', $messages);
        $this->assertStringNotContainsString('Unable to fetch IP information', $messages);
    }

    /**
     * Without this the misconfiguration is silent: the forum looks normal and
     * posts simply never get flags. The admin page is where it has to be
     * visible, since that is where the setting was chosen.
     */
    #[Test]
    public function the_admin_payload_flags_an_unavailable_service(): void
    {
        $response = $this->send(
            $this->request('GET', '/admin', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('fof-geoip.serviceUnavailable', (string) $response->getBody());
    }

    /**
     * Once a database is configured the driver behaves normally again, so the
     * short-circuit must be a live check rather than a latch.
     */
    #[Test]
    public function configuring_a_database_restores_lookups(): void
    {
        $dir = sys_get_temp_dir().'/fof-geoip-avail-'.bin2hex(random_bytes(6));
        mkdir($dir);

        (new \FoF\GeoIP\Tests\fixtures\MmdbBuilder('DBIP-Country-Lite'))
            ->add('8.8.8.0', 24, ['country' => ['iso_code' => 'US']])
            ->write($dir.'/country.mmdb');

        $this->setting('fof-geoip.services.maxmind.country_path', $dir.'/country.mmdb');

        try {
            $this->assertTrue($this->geoip()->isAvailable());
            $this->assertSame('US', $this->geoip()->get('8.8.8.8')?->getCountryCode());
        } finally {
            @unlink($dir.'/country.mmdb');
            @rmdir($dir);
        }
    }
}

/**
 * Captures log messages so the test can assert on what was reported.
 */
class CollectingLogger extends \Psr\Log\AbstractLogger
{
    /** @var string[] */
    public array $messages = [];

    public function log($level, $message, array $context = []): void
    {
        $this->messages[] = (string) $message;
    }
}
