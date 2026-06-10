<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Util;

class CountryCode
{
    /**
     * Normalise a user-supplied country code preference.
     *
     * Accepts a 2-letter ISO 3166-1 alpha-2 code (case-insensitive) and
     * returns it upper-cased. Anything else (empty, wrong length, non-alpha)
     * is treated as "no custom flag" and stored as null.
     */
    public static function sanitize($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = strtoupper(trim($value));

        if (preg_match('/^[A-Z]{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
