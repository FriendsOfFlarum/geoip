<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP;

use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Settings\Event\Saving;
use FoF\GeoIP\Api\GeoIP;
use Illuminate\Events\Dispatcher;

error_reporting(E_ALL);
ini_set('display_errors', 1);

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less')
        ->content(function (Document $document) {
            $document->payload['fof-geoip.services'] = array_keys(GeoIP::$services);
        }),
    new Extend\Locales(__DIR__.'/resources/locale'),
    function (Dispatcher $events) {
        $events->listen(Saving::class, Listeners\RemoveErrorsOnSettingsUpdate::class);
        $events->subscribe(Listeners\AddApiRelationships::class);
    },
];
