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
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Post\Post;
use Flarum\Settings\Event\Saving;
use FoF\GeoIP\Api\GeoIP;

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

    (new Extend\Model(Post::class))
        ->relationship('ip_info', Model\IPInfoRelationship::class),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Event())
        ->listen(Saving::class, Listeners\RemoveErrorsOnSettingsUpdate::class)
        ->subscribe(Listeners\RetrieveIP::class),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->relationship('ip_info', Api\AttachRelation::class),

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
        ->default('fof-geoip.service', 'ipapi')
        ->default('fof-geoip.showFlag', false)
        ->serializeToForum('fof-geoip.showFlag', 'fof-geoip.showFlag', 'boolval'),

    (new Extend\Routes('api'))
        ->get('/ip_info/{ip}', 'fof-geoip.api.ip_info', Api\Controller\ShowIpInfoController::class),

    (new Extend\Console())
        ->command(Console\LookupUnknownIPsCommand::class),

    (new Extend\User())
        ->registerPreference('showIPCountry', 'boolval', false),

    (new Extend\ApiSerializer(BasicUserSerializer::class))
        ->attributes(Api\BasicUserAttributes::class),

    (new Extend\ApiSerializer(CurrentUserSerializer::class))
        ->attributes(Api\CurrentUserAttributes::class),

    (new Extend\Conditional())
        ->whenExtensionEnabled('fof-default-user-preferences', fn () => [
            (new \FoF\DefaultUserPreferences\Extend\RegisterUserPreferenceDefault())
                ->default('showIPCountry', false, 'bool'),
        ]),
];
