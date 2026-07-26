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
use FoF\GeoIP\Jobs\RetrieveIP;
use FoF\GeoIP\Model\IPInfo;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;

class GeoIPRepository
{
    public function __construct(
        protected GeoIP $geoIP,
        protected Queue $queue
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
     * Queue a lookup for a post whose ip_info is known to be missing.
     *
     * Unlike the old retrieveForPost(), this never queries the database —
     * callers are expected to have already observed the miss on the loaded
     * relation. Returns the freshly retrieved info when the (sync) queue
     * driver executed the job immediately.
     */
    public function queueLookupForPost(Post $post): ?IPInfo
    {
        $ip = $post->ip_address;

        if (!$ip) {
            return null;
        }

        // If we're already retrieving this IP, we don't want to queue it again.
        if (!RetrieveIP::isQueued($ip)) {
            $this->queue->push(new RetrieveIP($ip));
        }

        // If using the sync queue driver (default), the job has already run.
        return Arr::get(RetrieveIP::$retrieved, $ip);
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
