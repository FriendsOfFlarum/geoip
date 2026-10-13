<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Access;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

/**
 * Whether an actor could be shown any ip_info: they can view IPs, they can see
 * countries, or flags are shown for the authors who opt in. Which posts' info
 * they then see is decided by each post's ipInfo relationship, and which fields
 * by IPInfoResource.
 *
 * viewIps is post-scoped, but checking it without a post gives the group-level
 * answer, which is all this needs.
 */
class CanSeeIPInfo
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(User $actor): bool
    {
        return $actor->can('discussion.viewIpsPosts')
            || $actor->can('fof-geoip.canSeeCountry')
            || (bool) $this->settings->get('fof-geoip.showFlag');
    }
}
