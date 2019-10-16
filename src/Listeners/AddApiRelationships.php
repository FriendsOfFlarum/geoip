<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Listeners;

use Flarum\Api\Controller;
use Flarum\Api\Event\Serializing;
use Flarum\Api\Event\WillGetData;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\GetModelRelationship;
use Flarum\Post\Post;
use FoF\GeoIP\IPInfo;
use FoF\GeoIP\IPInfoSerializer;
use FoF\GeoIP\Repositories\GeoIPRepository;
use Illuminate\Events\Dispatcher;

class AddApiRelationships
{
    /**
     * @var GeoIPRepository
     */
    protected $geoip;

    public function __construct(GeoIPRepository $geoip)
    {
        $this->geoip = $geoip;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetModelRelationship::class, [$this, 'addModelRelationship']);
        $events->listen(GetApiRelationship::class, [$this, 'addRelationship']);
        $events->listen(Serializing::class, [$this, 'passRelationship']);
        $events->listen(WillGetData::class, [$this, 'includeRelationship']);
    }

    public function addModelRelationship(GetModelRelationship $event)
    {
        if ($event->isRelationship(Post::class, 'ip_info')) {
            return $event->model->hasOne(IPInfo::class, 'address', 'ip_address');
        }
    }

    public function addRelationship(GetApiRelationship $event)
    {
        if ($event->isRelationship(PostSerializer::class, 'ip_info') && $event->serializer->getActor()->can('viewIps')) {
            return $event->serializer->hasOne($event->model, IPInfoSerializer::class, 'ip_info');
        }
    }

    public function passRelationship(Serializing $event)
    {
        if ($event->isSerializer(PostSerializer::class) && $event->serializer->getActor()->can('viewIps') && !$event->model->ip_info) {
            $event->model->setRelation('ip_info', $this->geoip->get($event->model->ip_address));
            $event->model->refresh();
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
