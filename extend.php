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
    (new Extend\ServiceProvider())
        ->register(Provider\ResolverProvider::class),

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
                        $visible = true;
                    } elseif ($actor->can('fof-geoip.canSeeCountry')) {
                        // Basic country info for users with the canSeeCountry permission.
                        $visible = true;
                    } elseif (!resolve(SettingsRepositoryInterface::class)->get('fof-geoip.showFlag')) {
                        // ...or, when the showFlag feature is enabled, if the post's
                        // author opted in via their showIPCountry preference.
                        //
                        // Evaluated last and short-circuited so the author preference
                        // is only consulted when it is actually decisive (showFlag on,
                        // and the actor lacks the broader permissions above). This
                        // preserves the original decision
                        // `viewCountry || (showFlag && authorPref)` exactly.
                        $visible = false;
                    } else {
                        // Resolve the author preference via the memoizing resolver
                        // rather than $post->user, which would lazy-load one user per
                        // post during serialization (firstPost, lastPost, and every
                        // post in a stream) — an N+1 on the hottest endpoints. The
                        // resolver reads the post's eager-loaded `user` relation when
                        // present (no query), and only falls back to a lookup for a
                        // post whose author was not loaded alongside it.
                        $visible = (bool) resolve(Repositories\AuthorFlagPreferenceResolver::class)
                            ->wantsFlagFor($post);
                    }

                    // Self-heal missing lookups: when the (eager-loaded) relation
                    // shows this post has no stored ip_info, queue a retrieval.
                    // This replaces the relationship's previous withDefault, whose
                    // closure Laravel invoked once per post BEFORE the batched
                    // relation query even ran — one wasted query per post on every
                    // list. Here the miss is only observed on the loaded relation.
                    if ($visible && $post->ip_address && $post->relationLoaded('ip_info') && !$post->getRelation('ip_info')) {
                        $info = resolve(Repositories\GeoIPRepository::class)->queueLookupForPost($post);

                        // With the sync queue driver the lookup already ran, so
                        // serialize the fresh data right away.
                        if ($info) {
                            $post->setRelation('ip_info', $info);
                        }
                    }

                    return $visible;
                }),
        ])
        ->endpoint(['show', 'index', 'update'], function (Endpoint\Show|Endpoint\Index|Endpoint\Update $endpoint): Endpoint\Show|Endpoint\Index|Endpoint\Update {
            // Eager load the relation alongside the posts: included to-one
            // relations are otherwise resolved one post at a time during
            // serialization — one ip_info query per post on the post stream.
            return $endpoint
                ->addDefaultInclude(['ipInfo'])
                ->eagerLoad(['ip_info']);
        }),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->endpoint(['show', 'index'], function (Endpoint\Show|Endpoint\Index $endpoint): Endpoint\Show|Endpoint\Index {
            // Same as above, for the posts included on discussion endpoints:
            // one batched ip_info load per relation path instead of one query
            // per included post.
            $endpoint = $endpoint
                ->addDefaultInclude(['firstPost.ipInfo'])
                ->eagerLoadWhenIncluded([
                    'firstPost' => ['firstPost.ip_info'],
                    'lastPost'  => ['lastPost.ip_info'],
                ]);

            // When the showFlag feature is on, the ip_info visibility check
            // needs each post author's showIPCountry preference. Load the
            // authors alongside the posts so the resolver reads them without
            // a query — otherwise it falls back to one lookup per distinct
            // author. Gated on the setting because with the flag off the
            // visibility decision never consults the author at all, and the
            // extra relation would be a wasted query.
            if ((bool) resolve(SettingsRepositoryInterface::class)->get('fof-geoip.showFlag')) {
                $endpoint = $endpoint->eagerLoadWhenIncluded([
                    'firstPost' => ['firstPost.user'],
                    'lastPost'  => ['lastPost.user'],
                ]);
            }

            return $endpoint;
        }),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            // Whether the current actor can see any ip_info data. Used by the
            // frontend to decide whether to include ipInfo in post list requests.
            // viewIps is post-scoped but checking without a model gives the global
            // group-level result, which is sufficient for this purpose.
            Schema\Boolean::make('fofGeoipCanSeeIpInfo')
                ->get(function (mixed $_, Context $context) {
                    $actor = $context->getActor();
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return $actor->can('discussion.viewIpsPosts')
                        || $actor->can('fof-geoip.canSeeCountry')
                        || (bool) $settings->get('fof-geoip.showFlag');
                }),
        ]),

    (new Extend\Settings())
        ->default('fof-geoip.service', 'ipapi')
        ->default('fof-geoip.showFlag', false)
        ->default('fof-geoip.allowCustomFlag', false)
        ->serializeToForum('fof-geoip.showFlag', 'fof-geoip.showFlag', 'boolval')
        ->serializeToForum('fof-geoip.allowCustomFlag', 'fof-geoip.allowCustomFlag', 'boolval'),

    (new Extend\Console())
        ->command(Console\LookupUnknownIPsCommand::class),

    (new Extend\User())
        ->registerPreference('showIPCountry', 'boolval', false)
        // Self-selected ISO 3166-1 alpha-2 country code, or null for none.
        ->registerPreference('customFlagCountry', [Util\CountryCode::class, 'sanitize'], null),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('showIPCountry')
                ->visible(function () {
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return (bool) $settings->get('fof-geoip.showFlag');
                })
                ->get(fn (\Flarum\User\User $user) => (bool) $user->getPreference('showIPCountry')),
            // The self-disclosed custom flag is public: it is intentionally
            // chosen by the user and is not derived from their IP, so it carries
            // no canSeeCountry gate. Only exposed while the feature is enabled;
            // if the admin later disables it, the field is omitted and the
            // frontend falls back to today's IP-based behaviour.
            Schema\Str::make('customFlagCountry')
                ->nullable()
                ->visible(function () {
                    $settings = resolve(SettingsRepositoryInterface::class);

                    return (bool) $settings->get('fof-geoip.allowCustomFlag');
                })
                ->get(fn (\Flarum\User\User $user) => Util\CountryCode::sanitize($user->getPreference('customFlagCountry'))),
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
            (new \FoF\DefaultUserPreferences\Extend\RegisterUserPreferenceDefault())
                ->default('customFlagCountry', '', 'string'),
        ]),

    new Extend\ApiResource(Api\Resource\IPInfoResource::class),

    (new Extend\Routes('api'))
        ->get('/geoip/test', 'fof-geoip.test', Api\Controller\TestGeoipController::class)
        ->get('/geoip/nominatim', 'fof-geoip.nominatim', Api\Controller\NominatimController::class),
];
