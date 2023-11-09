<?php

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
