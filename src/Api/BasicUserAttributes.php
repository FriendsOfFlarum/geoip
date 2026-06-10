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
use FoF\GeoIP\Util\CountryCode;

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

        // A user-selected custom flag is public (self-disclosed), so it is not
        // gated behind the `canSeeCountry` permission like IP-derived data.
        // It is only exposed while the admin feature is enabled; if the admin
        // later disables it, the stored preference is ignored and the attribute
        // is omitted, so the frontend falls back to today's IP-based behaviour.
        if ($this->settings->get('fof-geoip.allowCustomFlag')) {
            $attributes['customFlagCountry'] = CountryCode::sanitize($user->getPreference('customFlagCountry'));
        }

        return $attributes;
    }
}
