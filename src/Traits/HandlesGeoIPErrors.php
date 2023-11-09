<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Traits;

use FoF\GeoIP\Api\ServiceResponse;

trait HandlesGeoIPErrors
{
    protected function handleGeoIPError($service, $error): ServiceResponse
    {
        return (new ServiceResponse($service, true))
            ->setError($error);
    }
}
