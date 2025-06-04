<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Content;

use Flarum\Frontend\Document;
use FoF\GeoIP\Api\GeoIP;

class AdminPayload
{
    public function __invoke(Document $document)
    {
        $document->payload['fof-geoip.services'] = array_keys(GeoIP::$services);
        $document->payload['fof-geoip.proServices'] = array_keys(GeoIP::$proServices);

        $envFlags = [];

        foreach (array_keys(GeoIP::$proServices) as $service) {
            $setting = "fof-geoip.services.$service.access_key";
            $envName = preg_replace('/[^A-Z0-9_]/', '_', strtoupper(str_replace('.', '_', $setting)));
            $envFlags[$service] = (bool) env($envName);
        }

        $document->payload['fofGeoipEnv'] = $envFlags;
    }
}
