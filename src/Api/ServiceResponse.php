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
     * @var string
     */
    private $ip;

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
    private $latitude;

    /**
     * @var string
     */
    private $longitude;

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

    /**
     * @var bool
     */
    private $mobile;

    /**
     * @var ?string
     */
    private $as;

    /**
     * @var ?string
     */
    private $data_provider;

    public function __construct(?string $dataProvider, public bool $fake = false)
    {
        $this->setDataProvider($dataProvider);
    }

    public function setIP(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIP(): string
    {
        return $this->ip;
    }

    public function setCountryCode(?string $country_code): self
    {
        $this->country_code = $country_code;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->country_code;
    }

    public function setZipCode(?string $zip_code): self
    {
        $this->zip_code = $zip_code;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zip_code;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setIsp(?string $isp): self
    {
        $this->isp = $isp;

        return $this;
    }

    public function getIsp(): ?string
    {
        return $this->isp;
    }

    public function setOrganization(?string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setThreatLevel(?string $level): self
    {
        $this->threat_level = $level;

        return $this;
    }

    public function getThreatLevel(): ?string
    {
        return $this->threat_level;
    }

    public function setThreatType(?string $types): self
    {
        $this->threat_type = $types;

        return $this;
    }

    public function getThreatType(): ?string
    {
        return $this->threat_type;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setAs(?string $as): self
    {
        $this->as = $as;

        return $this;
    }

    public function getAs(): ?string
    {
        return $this->as;
    }

    public function setMobile(bool $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getMobile(): bool
    {
        return $this->mobile;
    }

    public function setDataProvider(?string $provider): self
    {
        $this->data_provider = $provider;

        return $this;
    }

    public function getDataProvider(): ?string
    {
        return $this->data_provider;
    }

    /**
     * @return array<string, mixed>
     */
    public function toJson(): array
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
