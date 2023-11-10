<?php

namespace FoF\GeoIP\Command;

use Flarum\User\User;

class FetchIPInfoBatch
{
    public function __construct(
        public array $ips,
        public ?User $actor = null,
        public bool $refresh = false
    ) {
    }
}
