<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Model;

use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IPInfoRelationship
{
    public function __invoke(Post $post): HasOne
    {
        // No withDefault here: Laravel's eager loading calls getDefaultFor()
        // for every parent BEFORE the batched relation query runs, so a
        // default closure that looks anything up executes once per post on
        // every list. Missing lookups are queued at serialization time
        // instead, where a genuine miss is observable (see extend.php).
        return $post->hasOne(IPInfo::class, 'address', 'ip_address');
    }
}
