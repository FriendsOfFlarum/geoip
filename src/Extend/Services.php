<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use FoF\GeoIP\Api\GeoIP;
use Illuminate\Contracts\Container\Container;

/**
 * Pins and configures the IP lookup service from code.
 *
 * Intended for deployments that ship their own geolocation databases — a
 * Docker image baking in DB-IP or MaxMind files, say — where the correct
 * driver and its paths are a property of the image rather than something an
 * administrator should have to set, or be able to break.
 *
 * ```php
 * (new FoF\GeoIP\Extend\Services())
 *     ->force('maxmind')
 *     ->configure('maxmind', [
 *         'country' => '/usr/share/GeoIP/dbip-country-lite.mmdb',
 *         'city'    => '/usr/share/GeoIP/dbip-city-lite.mmdb',
 *         'asn'     => '/usr/share/GeoIP/dbip-asn-lite.mmdb',
 *     ]);
 * ```
 *
 * `force()` also locks the admin selector, so the interface reflects what is
 * actually in effect rather than appearing to offer a choice that is ignored.
 */
class Services implements ExtenderInterface
{
    private ?string $forced = null;

    /** @var array<string, class-string> */
    private array $registering = [];

    /** @var array<string, array<string, string|null>> */
    private array $configuring = [];

    /**
     * Use this service regardless of the `fof-geoip.service` setting.
     */
    public function force(string $service): static
    {
        $this->forced = $service;

        return $this;
    }

    /**
     * Register an additional service implementation.
     *
     * @param class-string $serviceClass A {@see \FoF\GeoIP\Concerns\ServiceInterface}
     */
    public function register(string $name, string $serviceClass): static
    {
        $this->registering[$name] = $serviceClass;

        return $this;
    }

    /**
     * Supply configuration for a service in code, taking precedence over the
     * equivalent settings.
     *
     * For the offline driver the keys are `country`, `city` and `asn`, each an
     * absolute path to a .mmdb file.
     *
     * @param array<string, string|null> $config
     */
    public function configure(string $service, array $config): static
    {
        $this->configuring[$service] = array_merge($this->configuring[$service] ?? [], $config);

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        foreach ($this->registering as $name => $serviceClass) {
            GeoIP::$services[$name] = $serviceClass;
        }

        if ($this->forced !== null) {
            GeoIP::force($this->forced);
        }

        foreach ($this->configuring as $service => $config) {
            GeoIP::configure($service, $config);
        }
    }
}
