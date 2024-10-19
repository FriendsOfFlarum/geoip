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

use FoF\GeoIP\Model\IPInfo;

class IPInfoSerializer extends BasicIPInfoSerializer
{
    /**
     * @param IPInfo $ip
     *
     * @return array
     */
    protected function getDefaultAttributes($ip): array
    {
        $attrs = parent::getDefaultAttributes($ip);

        $moreAttrs = [
            'ip'                => $ip->address,
            'zipCode'           => $ip->zip_code,
            'latitude'          => $ip->latitude,
            'longitude'         => $ip->longitude,
            'isp'               => $ip->isp,
            'organization'      => $ip->organization,
            'as'                => $ip->as,
            'mobile'            => $ip->mobile,
            'threatLevel'       => $ip->threat_level,
            'threatType'        => $ip->threat_types,
            'error'             => $ip->error,
            'dataProvider'      => $ip->data_provider,
            'createdAt'         => $this->formatDate($ip->created_at),
            'updatedAt'         => $this->formatDate($ip->updated_at),
        ];

        return array_merge($attrs, $moreAttrs);
    }
}
