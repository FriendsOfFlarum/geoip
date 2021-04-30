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
        $schema->create('ip_info', function (Blueprint $table) {
            $table->string('address')->unique();

            $table->string('country_code')->nullable();
            $table->string('zip_code')->nullable();

            $table->string('isp')->nullable();
            $table->string('organization')->nullable();

            $table->string('threat_level')->nullable();
            $table->string('threat_types')->nullable();

            $table->string('error')->nullable();
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('ip_info');
    },
];
