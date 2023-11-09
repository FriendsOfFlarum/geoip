<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api;

class ServiceResponse implements \JsonSerializable
{
    /**
     * @var bool
     */
    public $fake;

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
     * @var string
     */
    private $threat_type;

    /**
     * @var ?string
     */
    private $error;

    public function __construct(bool $fake = false)
    {
        $this->fake = $fake;
    }

    public function setCountryCode(?string $country_code)
    {
        $this->country_code = $country_code;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->country_code;
    }

    public function setZipCode(?string $zip_code)
    {
        $this->zip_code = $zip_code;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    public function setIsp(?string $isp)
    {
        $this->isp = $isp;

        return $this;
    }

    public function getIsp(): ?string
    {
        return $this->isp;
    }

    public function setOrganization(?string $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setThreatLevel(?string $level)
    {
        $this->threat_level = $level;

        return $this;
    }

    public function getThreatLevel(): ?string
    {
        return $this->threat_level;
    }

    public function setThreatType(?string $types)
    {
        $this->threat_type = $types;

        return $this;
    }

    public function getThreatType(): ?string
    {
        return $this->threat_type;
    }

    public function setError(?string $error)
    {
        $this->error = $error;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function toJson()
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
