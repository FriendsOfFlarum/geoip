<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Concerns;

use FoF\GeoIP\Api\ServiceResponse;

interface ServiceInterface
{
    public function get(string $ip): ?ServiceResponse;

    /**
     * @param array<string> $ips
     *
     * @return array<ServiceResponse>
     */
    public function getBatch(array $ips): array;

    public function batchSupported(): bool;

    /**
     * The settings this service needs, for the admin page to render.
     *
     * Declared here rather than in the frontend so a service is configurable
     * by adding one class, and so the fields cannot drift from what the
     * service actually reads.
     *
     * Keyed by full setting key; each entry is:
     *   type         'text' | 'number' | 'boolean' (default 'text')
     *   label        translation key
     *   help         optional translation key
     *   placeholder  optional literal placeholder
     *   required     optional bool, default false
     *
     * @return array<string, array{type?: string, label: string, help?: string, placeholder?: string, required?: bool}>
     */
    public function settings(): array;
}
