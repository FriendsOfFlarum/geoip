<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Repositories;

use Flarum\Post\Post;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Command\FetchIPInfo;
use FoF\GeoIP\Concerns\OfflineServiceInterface;
use FoF\GeoIP\Jobs\RetrieveIP;
use FoF\GeoIP\Model\IPInfo;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;

class GeoIPRepository
{
    public function __construct(
        protected GeoIP $geoIP,
        protected Queue $queue,
        protected Dispatcher $bus
    ) {
    }

    /**
     * @param string|null $ip
     *
     * @return IPInfo|null
     */
    public function get(?string $ip): ?IPInfo
    {
        if (!$ip) {
            return null;
        }

        return $this->geoIP->getSaved($ip);
    }

    /**
     * Resolve the ip_info for a post whose record is known to be missing.
     */
    public function lookupForPost(Post $post): ?IPInfo
    {
        return $this->lookupForAddress($post->ip_address);
    }

    /**
     * Resolve the ip_info for an address whose record is known to be missing.
     *
     * Never queries the database for the record: callers are expected to have
     * already observed the miss on the loaded relation.
     *
     * An offline service resolves inline. The lookup is a memory-mapped file
     * read — microseconds — so dispatching a job to perform one costs far more
     * than the work, and on a real queue driver it also delays the result until
     * a worker picks it up. Hosted providers still queue: an HTTP round trip is
     * exactly the kind of work that should not block a request.
     *
     * Takes the address rather than the record: this began as the post-only
     * path, but the audit log needs the same behaviour and its rows are not
     * posts, so they would not satisfy that type hint.
     */
    public function lookupForAddress(?string $ip): ?IPInfo
    {
        if (!$ip) {
            return null;
        }

        if ($this->geoIP->getService() instanceof OfflineServiceInterface) {
            return $this->lookupInline($ip);
        }

        // If we're already retrieving this IP, we don't want to queue it again.
        if (!RetrieveIP::isQueued($ip)) {
            $this->queue->push(new RetrieveIP($ip));
        }

        // If using the sync queue driver (default), the job has already run.
        return Arr::get(RetrieveIP::$retrieved, $ip);
    }

    /**
     * Resolve and persist an address without touching the queue.
     *
     * Returns null when the service cannot answer — an unusable offline driver
     * fails identically for every address, so there is nothing to retry and
     * nothing worth storing.
     */
    public function lookupInline(string $ip): ?IPInfo
    {
        if (!$this->isValidIP($ip) || !$this->geoIP->isAvailable()) {
            return null;
        }

        $record = $this->bus->dispatch(new FetchIPInfo($ip));

        return $record->exists ? $record : null;
    }

    /**
     * Determine if the given value is a valid IP address.
     *
     * @param string $ip
     *
     * @return bool
     */
    public function isValidIP(?string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public function recordExistsForIP(string $ip): bool
    {
        return IPInfo::where('address', $ip)->exists();
    }
}
