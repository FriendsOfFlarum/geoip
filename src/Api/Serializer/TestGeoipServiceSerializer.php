<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;

class TestGeoipServiceSerializer extends AbstractSerializer
{
    protected $type = 'geoip-test';

    protected function getDefaultAttributes($model): array
    {
        return $model;
    }

    public function getId($model): string
    {
        return 'geoip-test';
    }
}
