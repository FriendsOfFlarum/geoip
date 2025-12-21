<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Tests\unit\Command;

use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for IP info fetch handlers.
 *
 * These tests verify that updateOrCreate() is used instead of the unsafe firstOrNew() + save()
 * pattern, which prevents race condition bugs when multiple concurrent requests try to insert
 * the same IP address.
 */
class FetchIPInfoHandlerTest extends TestCase
{
    #[Test]
    public function batch_handler_uses_array_diff_to_skip_existing_ips()
    {
        // This test verifies that we use array_diff() not Arr::except() which was a bug
        $batchHandlerCode = file_get_contents(__DIR__.'/../../../src/Command/FetchIPInfoBatchHandler.php');

        // Verify we use array_diff not Arr::except (which was a bug)
        $this->assertStringContainsString('array_diff', $batchHandlerCode, 'Should use array_diff to exclude existing IPs');
        $this->assertStringNotContainsString('Arr::except', $batchHandlerCode, 'Should not use Arr::except which expects keys not values');
    }

    #[Test]
    public function test_handler_uses_update_or_create_pattern()
    {
        // This is more of a code review test - verify the handlers use updateOrCreate
        $batchHandlerCode = file_get_contents(__DIR__.'/../../../src/Command/FetchIPInfoBatchHandler.php');
        $singleHandlerCode = file_get_contents(__DIR__.'/../../../src/Command/FetchIPInfoHandler.php');

        // Verify updateOrCreate is used (the safe pattern)
        $this->assertStringContainsString('updateOrCreate', $batchHandlerCode, 'Batch handler should use updateOrCreate to prevent race conditions');
        $this->assertStringContainsString('updateOrCreate', $singleHandlerCode, 'Single handler should use updateOrCreate to prevent race conditions');

        // Verify we don't use the unsafe pattern of firstOrNew + save
        $this->assertStringNotContainsString('firstOrNew(', $batchHandlerCode.') ... ->save()', 'Batch handler should not use unsafe firstOrNew + save pattern');

        // Verify we use array_diff not Arr::except (which was a bug)
        $this->assertStringContainsString('array_diff', $batchHandlerCode, 'Should use array_diff to exclude existing IPs');
        $this->assertStringNotContainsString('Arr::except', $batchHandlerCode, 'Should not use Arr::except which expects keys not values');
    }
}
