<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Listeners;

use Flarum\Post\Event\Saving as PostSaving;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Concerns\OfflineServiceInterface;
use FoF\GeoIP\Jobs;
use FoF\GeoIP\Repositories\GeoIPRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;

class RetrieveIP
{
    public function __construct(
        protected Queue $queue,
        protected GeoIPRepository $geo,
        protected GeoIP $geoIP
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(PostSaving::class, [$this, 'handlePost']);
    }

    public function retrieveIP(?string $ip): void
    {
        if ($ip === null || !$this->geo->isValidIP($ip) || $this->geo->recordExistsForIP($ip)) {
            return;
        }

        // An offline lookup is a local file read, so it is resolved here and
        // the record exists before the response is even built — the flag shows
        // on the first render rather than once a worker catches up. Queueing
        // one would cost far more than performing it.
        if ($this->geoIP->getService() instanceof OfflineServiceInterface) {
            $this->geo->lookupInline($ip);

            return;
        }

        $this->queue->push(new Jobs\RetrieveIP($ip));
    }

    public function handlePost(PostSaving $event): void
    {
        $this->retrieveIP($event->post->ip_address);
    }
}
