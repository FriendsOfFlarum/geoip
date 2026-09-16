<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Concerns;

/**
 * Marks a service that resolves addresses from local data rather than an HTTP
 * API.
 *
 * Declared separately from {@see ServiceInterface} so that adding it does not
 * break third-party services already implementing that contract.
 *
 * An offline lookup is a local file read — measured at 13µs with the
 * maxminddb C extension and 64µs in pure PHP, against roughly 400µs for a
 * single database query. Dispatching a queue job to perform one therefore
 * costs far more than the lookup itself, so callers may resolve these inline
 * during serialization instead of queueing.
 */
interface OfflineServiceInterface extends ServiceInterface
{
    /**
     * Whether lookups are served from local data and are cheap enough to run
     * synchronously in a request.
     */
    public function isOffline(): bool;

    /**
     * Whether the service currently has at least one usable database.
     *
     * A service can be selected but unusable — no paths configured, a path
     * that does not exist, or a file that is not a valid database — in which
     * case callers should fall back rather than treat every lookup as a miss.
     */
    public function isAvailable(): bool;

    /**
     * Per-database diagnostics for the admin panel, keyed by kind
     * (country/city/asn).
     *
     * @return array<string, array{configured: bool, available: bool, path: string|null, type: string|null, built: int|null, error: string|null}>
     */
    public function databaseStatus(): array;
}
