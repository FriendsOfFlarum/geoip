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

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Post\Post;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
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

    (new Extend\Frontend('common'))
        ->jsDirectory(__DIR__.'/js/dist/common'),

    (new Extend\Model(Post::class))
        ->relationship('ip_info', Model\IPInfoRelationship::class),

    (new Extend\ModelVisibility(Model\IPInfo::class))
        ->scope(Access\ScopeIPInfoVisibility::class),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Event())
        ->listen(Saving::class, Listeners\RemoveErrorsOnSettingsUpdate::class)
        ->subscribe(Listeners\RetrieveIP::class),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->fields(fn () => [
            Schema\Relationship\ToOne::make('ipInfo')
                ->type('ip_info')
                ->property('ip_info')
                ->includable()
                ->visible(function (Post $post, Context $context) {
                    $actor = $context->getActor();

                    // Full IP info for users with viewIps permission
                    if ($actor->can('viewIps', $post)) {
                        return true;
                    }

                    // Basic country info for users with canSeeCountry permission or user preference
                    $viewCountry = $actor->can('fof-geoip.canSeeCountry');
                    $showFlagsFeatureEnabled = resolve(SettingsRepositoryInterface::class)->get('fof-geoip.showFlag');
                    $userPreference = $post->user?->getPreference('showIPCountry');

                    return $viewCountry || ($showFlagsFeatureEnabled && $userPreference);
                }),
        ])
        ->endpoint(['show', 'index', 'update'], function (Endpoint\Show|Endpoint\Index|Endpoint\Update $endpoint): Endpoint\Show|Endpoint\Index|Endpoint\Update {
            return $endpoint->addDefaultInclude(['ipInfo']);
        }),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->endpoint(['show', 'index'], function (Endpoint\Show|Endpoint\Index $endpoint): Endpoint\Show|Endpoint\Index {
            return $endpoint->addDefaultInclude(['firstPost.ipInfo']);
        }),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Arr::make('fofGeoipLeafletMarkerUrls')
                ->get(function () {
                    $base = rtrim(resolve('filesystem')->disk('flarum-assets')->url(''), '/');

                    return [
                        'iconUrl'       => $base.'/extensions/fof-geoip/marker-icon.png',
                        'iconRetinaUrl' => $base.'/extensions/fof-geoip/marker-icon-2x.png',
                        'shadowUrl'     => $base.'/extensions/fof-geoip/marker-shadow.png',
                    ];
                }),

            // Whether the current actor can see any ip_info data. Used by the
            // frontend to decide whether to include ipInfo in post list requests.
            // viewIps is post-scoped but checking without a model gives the global
            // group-level result, which is sufficient for this purpose.
            Schema\Boolean::make('fofGeoipCanSeeIpInfo')
                ->get(function (mixed $_, Context $context) {
                    $actor = $context->getActor();
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return $actor->can('viewIps')
                        || $actor->can('fof-geoip.canSeeCountry')
                        || (bool) $settings->get('fof-geoip.showFlag');
                }),
        ]),

    (new Extend\Settings())
        ->default('fof-geoip.service', 'ipapi')
        ->default('fof-geoip.showFlag', false)
        ->serializeToForum('fof-geoip.showFlag', 'fof-geoip.showFlag', 'boolval'),

    (new Extend\Console())
        ->command(Console\LookupUnknownIPsCommand::class),

    (new Extend\User())
        ->registerPreference('showIPCountry', 'boolval', false),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('showIPCountry')
                ->visible(function () {
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return (bool) $settings->get('fof-geoip.showFlag');
                })
                ->get(fn (\Flarum\User\User $user) => (bool) $user->getPreference('showIPCountry')),
            Schema\Boolean::make('canSeeCountry')
                ->visible(fn (\Flarum\User\User $user, Context $context) => $user->id === $context->getActor()->id)
                ->get(
                    fn (mixed $_, Context $context) => $context->getActor()->can('fof-geoip.canSeeCountry')
                ),
        ]),

    (new Extend\Conditional())
        ->whenExtensionEnabled('fof-default-user-preferences', fn () => [
            (new \FoF\DefaultUserPreferences\Extend\RegisterUserPreferenceDefault())
                ->default('showIPCountry', false, 'bool'),
        ]),

    new Extend\ApiResource(Api\Resource\IPInfoResource::class),

    (new Extend\Routes('api'))
        ->get('/geoip/test', 'fof-geoip.test', Api\Controller\TestGeoipController::class)
        ->get('/geoip/nominatim', 'fof-geoip.nominatim', Api\Controller\NominatimController::class),
];
