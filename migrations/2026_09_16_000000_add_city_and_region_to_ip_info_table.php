<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * Both are nullable and unset for existing rows: only some services supply
 * them, and a row written before this migration has no value to backfill.
 * Re-running `php flarum fof:geoip:lookup --force` repopulates them wherever
 * the configured service can.
 *
 * Written with the schema builder rather than Migration::addColumns because
 * the latter emits `varchar()` with no length for a 'string' column.
 */
return [
    'up' => function (Builder $schema) {
        $schema->table('ip_info', function (Blueprint $table) {
            $table->string('city')->after('zip_code')->nullable();
            $table->string('region')->after('city')->nullable();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('ip_info', function (Blueprint $table) {
            $table->dropColumn(['city', 'region']);
        });
    },
];
