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

use Flarum\Messages\DialogMessage\Event\Created;

/**
 * Look up the address on a newly sent private message.
 *
 * Messages are the one place an address can be recorded without a post, so no
 * other path in the extension reaches them. Without this they stay bare until
 * a reader opens the message meta and the lazy lookup catches up.
 *
 * Registered only when flarum-messages is enabled — see the Conditional block
 * in extend.php, which is also what keeps this class from being loaded when
 * the event it type-hints does not exist.
 */
class RetrieveMessageIP
{
    public function __construct(protected RetrieveIP $retrieve)
    {
    }

    public function handle(Created $event): void
    {
        // Delegates: retrieveIP() already skips null, invalid and already-stored
        // addresses, and resolves inline for offline services.
        $this->retrieve->retrieveIP($event->message->ip_address);
    }
}
