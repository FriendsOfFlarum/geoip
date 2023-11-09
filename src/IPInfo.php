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

use Flarum\Database\AbstractModel;

/**
 * @property string $address
 * @property string|null $country_code
 * @property string|null $zip_code
 * @property string|null $isp
 * @property string|null $organization
 * @property string|null $threat_level
 * @property string|null $threat_types
 * @property string|null $error
 */
class IPInfo extends AbstractModel
{
    protected $table = 'ip_info';

    protected $fillable = [
        'country_code', 'zip_code',
        'isp', 'organization',
        'threat_level', 'threat_types',
        'error',
    ];
}
