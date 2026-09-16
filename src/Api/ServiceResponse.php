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
     * @var ?string
     */
    private $city;

    /**
     * @var ?string
     */
    private $region;

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
     * @var ?bool
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

    /**
     * The city name, where the service supplies one.
     *
     * Empty strings are normalised to null: ip-api returns "" rather than
     * omitting the field when it has no city, and an empty value would render
     * as a blank line rather than being hidden.
     */
    public function setCity(?string $city): self
    {
        $this->city = ($city === null || trim($city) === '') ? null : $city;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * The region, state or subdivision name. The human-readable form, not a
     * code: ip-api's `regionName` rather than `region`, matching the
     * `subdivisions[].names` the offline databases carry.
     */
    public function setRegion(?string $region): self
    {
        $this->region = ($region === null || trim($region) === '') ? null : $region;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
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

    /**
     * Whether the address belongs to a cellular network, or null when the
     * service cannot tell.
     *
     * Null rather than false for the unknown case: the offline databases carry
     * no connection-type data, and reporting false would claim the address is
     * definitely not mobile. Consumers show the field only when it is known.
     */
    public function setMobile(?bool $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getMobile(): ?bool
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
