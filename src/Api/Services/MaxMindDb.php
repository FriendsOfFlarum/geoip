<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Services;

use FoF\GeoIP\Api\ServiceResponse;
use FoF\GeoIP\Concerns\OfflineServiceInterface;
use MaxMind\Db\Reader;
use Psr\Log\LoggerInterface;

/**
 * Offline lookups against local MaxMind-format (.mmdb) databases.
 *
 * Deliberately not a {@see BaseGeoService}: that class exists to manage Guzzle,
 * API keys, rate limits and HTTP error handling, none of which apply to reading
 * a local file.
 *
 * Supports MaxMind's GeoLite2/GeoIP2 and DB-IP's Lite editions through one code
 * path — DB-IP publishes GeoLite2-compatible schemas, and its ASN database
 * declares `compat=GeoLite2-ASN` outright. Up to three databases are read and
 * merged:
 *
 *   country  country ISO code
 *   city     the above, plus coordinates and (MaxMind only) a postal code
 *   asn      autonomous system number and organisation
 *
 * All three are optional and independent. A country database alone is enough
 * for the flag feature, which is the smallest useful install (~8MB).
 *
 * Note that DB-IP's Lite editions carry no postal data whatsoever, so
 * `zip_code` stays null there; MaxMind's City databases populate it. Neither
 * vendor ships threat data offline, so threat level and type remain the
 * preserve of the HTTP services.
 */
class MaxMindDb implements OfflineServiceInterface
{
    public const KINDS = ['country', 'city', 'asn'];

    /** @var array<string, Reader> */
    private array $readers = [];

    /** @var array<string, string> Per-kind failure reason, for the admin panel. */
    private array $errors = [];

    private bool $opened = false;

    /**
     * @param array<string, string|null> $paths Absolute paths keyed by kind.
     */
    public function __construct(
        private array $paths,
        private LoggerInterface $logger
    ) {
    }

    public function isOffline(): bool
    {
        return true;
    }

    public function batchSupported(): bool
    {
        // Every lookup is a local read, so there is no batching win and no
        // remote endpoint to batch against. getBatch() simply loops.
        return false;
    }

    public function isAvailable(): bool
    {
        $this->open();

        return $this->readers !== [];
    }

    /**
     * One path per database, all optional and independent: the country
     * database alone drives country flags, city adds coordinates, ASN adds the
     * ISP and organisation.
     */
    public function settings(): array
    {
        $settings = [];

        foreach (self::KINDS as $kind) {
            $settings["fof-geoip.services.maxmind.{$kind}_path"] = [
                'type'        => 'text',
                'label'       => "fof-geoip.admin.settings.database_{$kind}_label",
                'placeholder' => "/usr/share/GeoIP/dbip-{$kind}-lite.mmdb",
                'required'    => false,
            ];
        }

        return $settings;
    }

    public function get(string $ip): ?ServiceResponse
    {
        $this->open();

        if ($this->readers === []) {
            return null;
        }

        $records = [];

        foreach ($this->readers as $kind => $reader) {
            $record = $this->read($reader, $kind, $ip);

            if ($record !== null) {
                $records[$kind] = $record;
            }
        }

        if ($records === []) {
            // Private and reserved ranges have no location and never will, so
            // they resolve to a record describing that rather than a miss.
            // This matches ip-api, which reports them as "private range" and
            // which IPApi stores rather than treating as an error — without a
            // stored record the address would be re-queued on every render.
            $reservedRange = $this->reservedRange($ip);

            if ($reservedRange !== null) {
                return (new ServiceResponse($this->dataProvider()))
                    ->setIP($ip)
                    ->setIsp($reservedRange)
                    ->setOrganization($reservedRange);
            }

            // A public address the databases do not cover. Coverage may
            // improve in a later edition, so nothing is stored.
            return null;
        }

        return $this->buildResponse($ip, $records);
    }

    public function getBatch(array $ips): array
    {
        $responses = [];

        foreach ($ips as $ip) {
            $response = $this->get($ip);

            if ($response !== null) {
                $responses[] = $response;
            }
        }

        return $responses;
    }

    public function databaseStatus(): array
    {
        $this->open();

        $status = [];

        foreach (self::KINDS as $kind) {
            $path = $this->paths[$kind] ?? null;
            $reader = $this->readers[$kind] ?? null;
            $metadata = $reader?->metadata();

            $status[$kind] = [
                // Whether a path was given at all. Distinguishes "not set" —
                // legitimate, since every database is optional — from "set but
                // unusable", which is a misconfiguration worth surfacing.
                'configured' => (bool) $path,
                'available'  => $reader !== null,
                'path'       => $path ?: null,
                'type'       => $metadata->databaseType ?? null,
                'built'      => $metadata->buildEpoch ?? null,
                'error'      => $this->errors[$kind] ?? null,
            ];
        }

        return $status;
    }

    /**
     * Open each configured database once per instance.
     *
     * A path that is missing, unreadable or not a database is recorded and
     * skipped rather than thrown: one broken file must not take out the
     * others, and a lookup is not the place to surface a configuration error.
     * The admin panel reports them via databaseStatus().
     */
    private function open(): void
    {
        if ($this->opened) {
            return;
        }

        $this->opened = true;

        foreach (self::KINDS as $kind) {
            $path = $this->paths[$kind] ?? null;

            if (!$path) {
                continue;
            }

            if (!is_file($path) || !is_readable($path)) {
                $this->errors[$kind] = 'File not found or not readable';
                continue;
            }

            try {
                $this->readers[$kind] = new Reader($path);
            } catch (\Throwable $e) {
                $this->errors[$kind] = $e->getMessage();
                $this->logger->warning("[fof/geoip] Could not open $kind database at $path: ".$e->getMessage());
            }
        }
    }

    /**
     * Classify an address that no database covers.
     *
     * Returns the wording ip-api uses for the same address, so a record looks
     * the same whichever service produced it, or null for a public address
     * that is simply absent.
     */
    private function reservedRange(string $ip): ?string
    {
        // Not an address at all — nothing to classify.
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        // Public, routable addresses are neither private nor reserved.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false) {
            return 'private range';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'reserved range';
        }

        // Not a valid address at all.
        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function read(Reader $reader, string $kind, string $ip): ?array
    {
        try {
            $record = $reader->get($ip);
        } catch (\InvalidArgumentException $e) {
            // Malformed address. Callers validate first, but a bad value must
            // never escape into post serialization.
            return null;
        } catch (\Throwable $e) {
            $this->logger->warning("[fof/geoip] Lookup failed in $kind database for $ip: ".$e->getMessage());

            return null;
        }

        return is_array($record) ? $record : null;
    }

    /**
     * @param array<string, array<string, mixed>> $records
     */
    private function buildResponse(string $ip, array $records): ServiceResponse
    {
        $response = (new ServiceResponse($this->dataProvider()))->setIP($ip);

        // City databases embed the same country block, so either will do and a
        // country database is optional when a city one is present.
        $geo = $records['city'] ?? $records['country'] ?? [];

        if ($country = $this->arrayGet($geo, 'country.iso_code')) {
            $response->setCountryCode($country);
        }

        if ($postal = $this->arrayGet($geo, 'postal.code')) {
            $response->setZipCode((string) $postal);
        }

        // Both vendors localise place names, keyed by language. English is the
        // only language every edition is guaranteed to carry, and the value is
        // stored rather than rendered per viewer, so it is the one used.
        $response->setCity($this->arrayGet($geo, 'city.names.en'));

        // The first subdivision is the largest administrative division — the
        // state or county. Deeper entries are progressively finer and are not
        // present in the Lite editions.
        $response->setRegion($this->arrayGet($geo, 'subdivisions.0.names.en'));

        $latitude = $this->arrayGet($geo, 'location.latitude');
        $longitude = $this->arrayGet($geo, 'location.longitude');

        if ($latitude !== null && $longitude !== null) {
            $response->setLatitude((string) $latitude);
            $response->setLongitude((string) $longitude);
        }

        $asn = $records['asn'] ?? [];
        $number = $asn['autonomous_system_number'] ?? null;
        $organization = $asn['autonomous_system_organization'] ?? null;

        if ($organization) {
            // The HTTP services report ISP and organisation separately; offline
            // databases only carry the AS organisation, so it stands for both.
            $response->setIsp((string) $organization)
                ->setOrganization((string) $organization);
        }

        if ($number) {
            // Matches the "AS15169 Google LLC" form the ip-api services return.
            $response->setAs(trim("AS$number ".(string) $organization));
        }

        // Offline databases carry no threat intelligence. Left unset rather
        // than defaulted, so the absence is visible instead of looking like a
        // clean result.
        return $response;
    }

    /**
     * The database type of whichever database answered, e.g.
     * "DBIP-City-Lite" or "GeoLite2-City", so the stored record shows where
     * the data came from.
     */
    private function dataProvider(): string
    {
        foreach (['city', 'country', 'asn'] as $kind) {
            // `?->` guards a null reader but not a missing array key.
            $type = ($this->readers[$kind] ?? null)?->metadata()->databaseType;

            if ($type) {
                return $type;
            }
        }

        return 'maxmind';
    }

    private function arrayGet(array $array, string $path): mixed
    {
        foreach (explode('.', $path) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return null;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}
