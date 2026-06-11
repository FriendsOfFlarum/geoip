<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\unit\Util;

use FoF\GeoIP\Util\CountryCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CountryCodeTest extends TestCase
{
    #[Test]
    public function accepts_a_valid_two_letter_code()
    {
        $this->assertEquals('GB', CountryCode::sanitize('GB'));
        $this->assertEquals('US', CountryCode::sanitize('US'));
    }

    #[Test]
    public function uppercases_and_trims_input()
    {
        $this->assertEquals('GB', CountryCode::sanitize('gb'));
        $this->assertEquals('FR', CountryCode::sanitize(' fr '));
    }

    #[Test]
    #[DataProvider('invalidValues')]
    public function rejects_invalid_values($value)
    {
        $this->assertNull(CountryCode::sanitize($value));
    }

    public static function invalidValues(): array
    {
        return [
            'empty string'  => [''],
            'whitespace'    => ['   '],
            'one letter'    => ['G'],
            'three letters' => ['GBR'],
            'digits'        => ['12'],
            'mixed'         => ['G1'],
            'null'          => [null],
            'boolean'       => [true],
            'integer'       => [42],
            'array'         => [['GB']],
        ];
    }
}
