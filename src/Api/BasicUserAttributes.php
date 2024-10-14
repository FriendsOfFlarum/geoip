<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
