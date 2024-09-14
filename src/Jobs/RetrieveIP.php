<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Jobs;

use Flarum\Queue\AbstractJob;
use FoF\GeoIP\Command\FetchIPInfo;
use FoF\GeoIP\Model\IPInfo;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Arr;

class RetrieveIP extends AbstractJob
{
    public static ?string $onQueue = null;

    // Keep track of IPs we're retrieving & have already retrieved to avoid duplicate requests in the same request/queue worker
    public static array $queued = [];
    public static array $retrieving = [];
    public static array $retrieved = [];

    public function __construct(protected string $ip)
    {
        self::$queued[] = $ip;

        if (static::$onQueue) {
            $this->onQueue(static::$onQueue);
        }
    }

    public function handle(Repository $cache, Dispatcher $bus): void
    {
        $ip = $this->ip;
        $cacheKey = "fof-geoip.retrieving.$ip";

        // Return if 1) no IP, 2) already retrieving this IP, 3) already retrieved this IP, 4) already cached this IP
        if (!$ip || self::isRetrieving($ip) || Arr::has(static::$retrieved, $ip) || $cache->has($cacheKey)) {
            return;
        }

        // Add to retrieving list and cache (don't attempt again for 1 hour if job fails)
        // Errors from the API do not count as job failures.
        static::$retrieving[] = $ip;
        $cache->add($cacheKey, true, 60);

        /** @var IPInfo $ipInfo */
        $ipInfo = $bus->dispatch(new FetchIPInfo($ip));

        if ($ipInfo->exists) {
            static::$retrieving = array_diff(static::$retrieving, [$ip]);
            $cache->forget("fof-geoip.retrieving.$ip");
        }
    }

    public static function isRetrieving($ip): bool
    {
        return in_array($ip, static::$retrieving);
    }

    public static function isQueued($ip): bool
    {
        return in_array($ip, static::$queued) || self::isRetrieving($ip);
    }
}
