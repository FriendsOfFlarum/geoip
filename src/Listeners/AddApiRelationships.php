<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Listeners;

use Flarum\Api\Controller;
use Flarum\Api\Event\WillGetData;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\GetApiRelationship;
use FoF\GeoIP\IPInfoSerializer;
use Illuminate\Events\Dispatcher;

class AddApiRelationships
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetApiRelationship::class, [$this, 'addRelationship']);
        $events->listen(WillGetData::class, [$this, 'includeRelationship']);
    }

    public function addRelationship(GetApiRelationship $event)
    {
        if ($event->isRelationship(PostSerializer::class, 'ip_info') && $event->serializer->getActor()->can('viewIps')) {
            return $event->serializer->hasOne($event->model, IPInfoSerializer::class, 'ip_info');
        }
    }

    public function includeRelationship(WillGetData $event)
    {
        if ($event->isController(Controller\ListPostsController::class)
            || $event->isController(Controller\ShowPostController::class)
            || $event->isController(Controller\CreatePostController::class)
            || $event->isController(Controller\UpdatePostController::class)) {
            $event->addInclude('ip_info');
        }

        if ($event->isController(Controller\ShowDiscussionController::class)) {
            $event->addInclude('posts.ip_info');
        }
    }
}
