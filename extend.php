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

use Flarum\Api\Controller;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Post\Post;
use Flarum\Settings\Event\Saving;
use FoF\Components\Extend\AddFofComponents;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Repositories\GeoIPRepository;

return [
    new AddFofComponents(),
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

    (new Extend\Event())
        ->listen(Saving::class, Listeners\RemoveErrorsOnSettingsUpdate::class),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->relationship('ip_info', function (PostSerializer $serializer, Post $model) {
            if ($serializer->getActor()->can('viewIps')) {
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
];
