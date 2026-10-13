<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Access;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeIPInfoVisibility
{
    public function __construct(
        protected CanSeeIPInfo $canSeeIPInfo
    ) {
    }

    public function __invoke(User $actor, Builder $query): void
    {
        // What reaches a reader is decided per post by its ipInfo relationship
        // and per field by IPInfoResource, so rows need only be kept from a
        // guest that nothing could show one to. Keeping guests out regardless
        // emptied the batched load of their posts' ip_info, which extend.php
        // then took for missing records and looked up again one post at a time.
        if ($actor->isGuest() && !($this->canSeeIPInfo)($actor)) {
            $query->whereRaw('FALSE');
        }
    }
}
