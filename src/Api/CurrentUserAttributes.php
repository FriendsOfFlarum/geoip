<?php

namespace FoF\GeoIP\Api;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\User\User;

class CurrentUserAttributes
{
    public function __invoke(CurrentUserSerializer $serializer, User $user, array $attributes): array
    {
        if ($serializer->getActor()->can('fof-geoip.canSeeCountry')) {
            $attributes['canSeeCountry'] = true;
        }
        
        return $attributes;
    }
}
