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
     * @param Post $post
     *
     * @return IPInfo|void
     */
    public function retrieveForPost(Post $post)
    {
        $ip = $post->ip_address;
        $info = $this->get($ip);

        // Return the info or null. If we're already retrieving this IP, we don't want to queue it again
        if ($info || !$ip || RetrieveIP::isQueued($ip)) {
            return $info;
        }

        $this->queue->push(new RetrieveIP($ip));

        // If using the sync queue driver (default), the job will be executed immediately
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
