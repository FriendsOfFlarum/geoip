<?php

namespace FoF\GeoIP\Api;

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Post\Post;
use FoF\GeoIP\Api\Serializer\BasicIPInfoSerializer;
use FoF\GeoIP\Api\Serializer\IPInfoSerializer;
use Tobscure\JsonApi\Relationship;

class AttachRelation
{
    public function __invoke(PostSerializer $serializer, Post $post): Relationship
    {
        $viewIPs = $serializer->getActor()->can('viewIps', $post);

        return $serializer->hasOne($post, $viewIPs ? IPInfoSerializer::class : BasicIPInfoSerializer::class, 'ip_info');
    }
}
