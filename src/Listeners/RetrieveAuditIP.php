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

use Flarum\Audit\AuditLog;

/**
 * Look up the address on a newly recorded audit entry.
 *
 * Audit rows capture failed logins and blocked registrations — the addresses a
 * moderator most wants placed, and ones that never produce a post, so no other
 * path in the extension reaches them. Without this they stay bare until the
 * console command is next run.
 *
 * flarum/audit publishes no events of its own, but AuditLog extends
 * AbstractModel and AuditLogger::log() persists through $log->save(), so
 * Eloquent's model events fire. Listening for the created event rather than
 * saving: the row is already written, and a lookup must not be able to affect
 * whether the audit entry itself is recorded.
 *
 * Registered only when flarum-audit is enabled — see the Conditional block in
 * extend.php, which is also what keeps this class from being loaded when the
 * AuditLog class it type-hints does not exist.
 */
class RetrieveAuditIP
{
    public function __construct(protected RetrieveIP $retrieve)
    {
    }

    public function handle(AuditLog $log): void
    {
        // Delegates rather than reimplementing: retrieveIP() already skips
        // null, invalid and already-stored addresses, and picks inline
        // resolution over the queue for offline services. Doing that here too
        // would be a second copy to keep in step.
        $this->retrieve->retrieveIP($log->ip_address);
    }
}
