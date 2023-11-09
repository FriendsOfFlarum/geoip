<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('ip_info', [
    'created_at' => ['timestamp', 'nullable' => false, 'useCurrent' => true],
    'updated_at' => ['datetime', 'nullable' => true],
]);
