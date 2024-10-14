<?php

namespace FoF\GeoIP\Api;

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

class BasicUserAttributes
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
        
    }
    
    public function __invoke(BasicUserSerializer $serializer, User $user, array $attributes): array
    {
        if ($this->settings->get('fof-geoip.showFlag')) {
            $attributes['showIPCountry'] = (bool) $user->getPreference('showIPCountry');
        }
        
        return $attributes;
    }
}
