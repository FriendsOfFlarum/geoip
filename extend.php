<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP;

use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Post\Post;
use Flarum\Settings\Event\Saving;
use FoF\GeoIP\IPInfo;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Repositories\GeoIPRepository;
use Illuminate\Events\Dispatcher;

error_reporting(E_ALL);
ini_set('display_errors', 1);

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->content(function (Document $document) {
            $document->payload['fof-geoip.services'] = array_keys(GeoIP::$services);
        }),
    (new Extend\Model(Post::class))->relationship('ip_info', function ($model) {
        return $model->hasOne(IPInfo::class, 'address', 'ip_address')
        ->withDefault(function ($instance, $submodel) {
            return app(GeoIpRepository::class)->get($submodel->ip_address);
        });
    }),
    new Extend\Locales(__DIR__.'/resources/locale'),
    function (Dispatcher $events) {
        $events->listen(Saving::class, Listeners\RemoveErrorsOnSettingsUpdate::class);
        $events->subscribe(Listeners\AddApiRelationships::class);
    },
];
