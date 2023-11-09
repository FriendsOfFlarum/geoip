<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Listeners;

use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;

class RemoveErrorsOnSettingsUpdate
{
    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function handle(Saving $event)
    {
        foreach ($event->settings as $key => $value) {
            if (!Str::startsWith($key, 'fof-geoip.service')) {
                continue;
            }

            $service = $value;

            if ($key !== 'fof-geoip.service') {
                $matches = null;
                preg_match('/fof-geoip\.services\.(.+?)\./m', $key, $matches);

                $service = $matches[1] ?? $value;
            }

            $this->settings->delete("fof-geoip.services.$service.error");
            $this->settings->delete("fof-geoip.services.$service.last_error_time");
        }
    }
}
