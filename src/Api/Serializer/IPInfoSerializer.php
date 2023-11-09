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

class IPInfoSerializer extends AbstractSerializer
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
            'zipCode'           => $ip->zip_code,
            'isp'               => $ip->isp,
            'organization'      => $ip->organization,
            'threatLevel'       => $ip->threat_level,
            'threatType'        => $ip->threat_types,
            'error'             => $ip->error,
            'createdAt'         => $this->formatDate($ip->created_at),
            'updatedAt'         => $this->formatDate($ip->updated_at),
        ];
    }

    /**
     * @param IPInfo $model
     *
     * @return string
     */
    public function getId($model)
    {
        return $model->address;
    }
}
