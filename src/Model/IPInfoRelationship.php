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

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IPInfoRelationship
{
    /**
     * Attaches ip_info to any model with an `ip_address` column.
     *
     * Posts were the original and only consumer, but the audit log records
     * addresses in an identically named column and benefits from the same
     * batched load, so the parameter is the base model rather than Post.
     */
    public function __invoke(AbstractModel $model): HasOne
    {
        // No withDefault here: Laravel's eager loading calls getDefaultFor()
        // for every parent BEFORE the batched relation query runs, so a
        // default closure that looks anything up executes once per row on
        // every list. Missing lookups are queued at serialization time
        // instead, where a genuine miss is observable (see extend.php).
        return $model->hasOne(IPInfo::class, 'address', 'ip_address');
    }
}
