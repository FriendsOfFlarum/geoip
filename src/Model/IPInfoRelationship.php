<?php

namespace FoF\GeoIP\Model;

use Flarum\Post\Post;
use FoF\GeoIP\Repositories\GeoIPRepository;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IPInfoRelationship
{
    protected $geoIP;
    
    public function __construct(GeoIPRepository $geoIP)
    {
        $this->geoIP = $geoIP;
    }
    
    public function __invoke(Post $post): HasOne
    {
        return $post->hasOne(IPInfo::class, 'address', 'ip_address')
            ->withDefault(function (IPInfo $instance, Post $submodel) {
                return $this->geoIP->retrieveForPost($submodel);
            });
    }
}
