<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Provider;

use Flarum\Foundation\AbstractServiceProvider;
use FoF\GeoIP\Repositories\AuthorFlagPreferenceResolver;

class ResolverProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Singleton so the author-preference memo is shared for the lifetime of
        // a request (Flarum rebuilds the container per request), turning the
        // per-post author lookups in the ip_info visibility check into a single
        // cached resolution per author.
        $this->container->singleton(AuthorFlagPreferenceResolver::class);
    }
}
