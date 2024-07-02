<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Command;

use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Repositories\GeoIPRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

class FetchIPInfoHandler
{
    public function __construct(
        protected GeoIP $geoip,
        protected GeoIPRepository $repository
    ) {
    }

    public function handle(FetchIPInfo $command): IPInfo
    {
        if (!$this->repository->isValidIP($command->ip)) {
            throw new RuntimeException("Invalid IP address: {$command->ip}");
        }

        $ipInfo = IPInfo::query()->firstOrNew(['address' => $command->ip]);

        if (!$ipInfo->exists || $command->refresh) {
            $response = $this->geoip->get($command->ip);

            if (!$response || $response->fake) {
                throw new ModelNotFoundException("Unable to fetch IP information for IP: {$command->ip}");
            }

            if ($response) {
                $ipInfo->address = $command->ip;
                $ipInfo->fill($response->toJSON());
                $ipInfo->save();
            }
        }

        return $ipInfo;
    }
}
