<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Model;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;

/**
 * @property string      $address
 * @property string|null $country_code
 * @property string|null $zip_code
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $isp
 * @property string|null $organization
 * @property string|null $as
 * @property bool|null   $mobile
 * @property string|null $threat_level
 * @property string|null $threat_types
 * @property string|null $error
 * @property string|null $data_provider
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class IPInfo extends AbstractModel
{
    protected $table = 'ip_info';

    protected $primaryKey = 'address';

    protected $fillable = [
        'country_code', 'zip_code', 'latitude', 'longitude',
        'isp', 'organization', 'as', 'mobile',
        'threat_level', 'threat_types',
        'error', 'data_provider',
    ];

    public $timestamps = true;

    public $casts = [
        'mobile'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'address'    => 'string',
    ];
}
