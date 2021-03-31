<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP;

use Flarum\Api\Serializer\AbstractSerializer;

class IPInfoSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'ip_info';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($ip)
    {
        return [
            'countryCode'       => $ip->country_code,
            'zipCode'           => $ip->zip_code,
            'isp'               => $ip->isp,
            'organization'      => $ip->organization,
            'threatLevel'       => $ip->threat_level,
            'threatType'        => $ip->threat_type,
            'error'             => $ip->error,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        return $model->address;
    }
}
