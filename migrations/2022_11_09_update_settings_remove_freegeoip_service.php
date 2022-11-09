<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        $db->table('settings')
            ->where('key', 'fof-geoip.service')
            ->where('value', 'freegeoip')
            ->delete();
    },
    'down' => function (Builder $schema) {
        //
    },
];
