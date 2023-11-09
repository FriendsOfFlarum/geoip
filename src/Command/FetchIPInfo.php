<?php

namespace FoF\GeoIP\Command;

use Flarum\User\User;

class FetchIPInfo
{
    public function __construct(
        public string $ip,
        public ?User $actor = null,
        public bool $refresh = false
    ) {}
}
