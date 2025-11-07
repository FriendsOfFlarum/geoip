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
    public function __invoke(User $actor, Builder $query): void
    {
        // IP info is visible to all authenticated users
        // But we don't show anything to guests
        if ($actor->isGuest()) {
            $query->whereRaw('FALSE');
        }

        // No additional scoping needed - all authenticated users can see IP info records
        // Field-level visibility is handled in the Resource
    }
}
