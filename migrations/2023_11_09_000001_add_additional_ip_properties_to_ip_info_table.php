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

return [
    'up' => function (Builder $schema) {
        $schema->table('ip_info', function (Blueprint $table) {
            $table->string('latitude')->after('zip_code')->nullable();
            $table->string('longitude')->after('latitude')->nullable();
            $table->string('as')->after('organization')->nullable();
            $table->boolean('mobile')->after('address')->nullable();
            $table->string('data_provider')->after('error')->nullable();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('ip_info', function (Blueprint $table) {
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('as');
            $table->dropColumn('mobile');
            $table->dropColumn('data_provider');
        });
    },
];
