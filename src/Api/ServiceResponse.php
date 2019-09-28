<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api;

class ServiceResponse implements \JsonSerializable
{
    /**
     * @var string
     */
    private $country_code;

    /**
     * @var string
     */
    private $zip_code;

    /**
     * @var string
     */
    private $isp;

    /**
     * @var string
     */
    private $organization;

    /**
     * @var string
     */
    private $threat_level;

    /**
     * @var array
     */
    private $threat_types;

    /**
     * @var
     */
    private $error;

    public function __construct()
    {
    }

    public function setCountryCode(string $country_code)
    {
        $this->country_code = $country_code;

        return $this;
    }

    public function setZipCode(string $zip_code)
    {
        $this->zip_code = $zip_code;

        return $this;
    }

    public function setIsp(string $isp)
    {
        $this->isp = $isp;

        return $this;
    }

    public function setOrganization(string $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    public function setThreatLevel(string $level)
    {
        $this->threat_level = $level;

        return $this;
    }

    public function setThreatTypes(?array $types)
    {
        $this->threat_types = $types;

        return $this;
    }

    public function setError(string $error)
    {
        $this->error = $error;

        return $this;
    }

    public function toJson()
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
