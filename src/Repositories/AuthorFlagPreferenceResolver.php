<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Repositories;

use Flarum\User\User;

/**
 * Resolves a post author's `showIPCountry` preference without an N+1.
 *
 * The ip_info field's visibility callback runs for every post being
 * serialized (firstPost, lastPost, and every post in a stream), and when the
 * showFlag feature is enabled it needs the post author's showIPCountry
 * preference. Reading $post->user there lazy-loads one user per post.
 *
 * This resolver memoizes preferences by user id, and reads the value straight
 * off the loaded User model via the framework's own getPreference() — so the
 * registered default and transformer are applied exactly as anywhere else. It
 * is bound as a singleton, which in Flarum's per-request container makes the
 * memo request-scoped: many posts by the same author cost a single lookup, and
 * once the cache is warm there are no further queries.
 */
class AuthorFlagPreferenceResolver
{
    /** @var array<int, bool> userId => showIPCountry */
    protected array $cache = [];

    public function wantsFlag(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        if (!array_key_exists($userId, $this->cache)) {
            $this->load([$userId]);
        }

        return $this->cache[$userId];
    }

    /**
     * Warm the cache for many authors in one query. Optional — wantsFlag() is
     * correct on its own — but lets the field pre-load every serialized post's
     * author at once instead of one query per distinct author.
     *
     * @param int[] $userIds
     */
    public function load(array $userIds): void
    {
        $missing = array_values(array_unique(array_filter(
            $userIds,
            fn ($id) => $id !== null && !array_key_exists($id, $this->cache)
        )));

        if (empty($missing)) {
            return;
        }

        $users = User::query()->whereIn('id', $missing)->get()->keyBy('id');

        foreach ($missing as $id) {
            /** @var User|null $user */
            $user = $users->get($id);
            $this->cache[$id] = $user !== null && (bool) $user->getPreference('showIPCountry');
        }
    }
}
