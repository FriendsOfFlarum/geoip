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
use FoF\GeoIP\Model\IPInfo;
use InvalidArgumentException;

class BasicIPInfoSerializer extends AbstractSerializer
{
    protected $type = 'ip_info';

    /**
     * @param IPInfo $ip
     *
     * @return array
     */
    protected function getDefaultAttributes($ip): array
    {
        if (!($ip instanceof IPInfo)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.IPInfo::class
            );
        }

        return [
            'countryCode'       => $ip->country_code,
        ];
    }

    /**
     * @param IPInfo $model
     *
     * @return string
     */
    public function getId($model)
    {
        return hash('sha256', $model->address);
    }
}
