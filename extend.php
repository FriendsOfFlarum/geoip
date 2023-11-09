<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP;

use Flarum\Api\Controller;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Post\Post;
use Flarum\Settings\Event\Saving;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\Serializer\IPInfoSerializer;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Repositories\GeoIPRepository;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->content(function (Document $document) {
            $document->payload['fof-geoip.services'] = array_keys(GeoIP::$services);
        }),

    (new Extend\Model(Post::class))->relationship('ip_info', function (Post $model) {
        return $model->hasOne(IPInfo::class, 'address', 'ip_address')
        ->withDefault(function ($instance, $submodel) {
            return resolve(GeoIPRepository::class)->retrieveForPost($submodel);
        });
    }),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Event())
        ->listen(Saving::class, Listeners\RemoveErrorsOnSettingsUpdate::class),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->relationship('ip_info', function (PostSerializer $serializer, Post $model) {
            if ($serializer->getActor()->can('viewIps', $model)) {
                return $serializer->hasOne($model, IPInfoSerializer::class, 'ip_info');
            }
        }),

    (new Extend\ApiController(Controller\ListPostsController::class))
        ->addInclude('ip_info'),

    (new Extend\ApiController(Controller\ShowPostController::class))
        ->addInclude('ip_info'),

    (new Extend\ApiController(Controller\CreatePostController::class))
        ->addInclude('ip_info'),

    (new Extend\ApiController(Controller\UpdatePostController::class))
        ->addInclude('ip_info'),

    (new Extend\ApiController(Controller\ShowDiscussionController::class))
        ->addInclude('posts.ip_info'),

    (new Extend\Settings())
        ->default('fof-geoip.service', 'ipapi'),

    (new Extend\Routes('api'))
        ->get('/ip_info/{ip}', 'fof-geoip.api.ip_info', Api\Controller\ShowIpInfoController::class),
    
    (new Extend\Console())
        ->command(Console\LookupUnknownIPsCommand::class),
];
