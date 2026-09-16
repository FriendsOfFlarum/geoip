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
use Psr\Log\LoggerInterface;

class FetchIPInfoHandler
{
    public function __construct(
        protected GeoIP $geoip,
        protected GeoIPRepository $repository,
        protected LoggerInterface $log
    ) {
    }

    public function handle(FetchIPInfo $command): IPInfo
    {
        if (!$this->repository->isValidIP($command->ip)) {
            $this->log->info('Invalid IP address: '.$command->ip);

            return IPInfo::query()->firstOrNew(['address' => $command->ip]);
        }

        $ipInfo = IPInfo::query()->firstOrNew(['address' => $command->ip]);

        if (!$ipInfo->exists || $command->refresh) {
            // A service that is selected but unusable — an offline driver with
            // no readable database — fails for every address alike. Report the
            // configuration fault once rather than logging a lookup failure per
            // address, which buries the real cause and never stops.
            if (!$this->geoip->isAvailable()) {
                $this->log->warning('[fof/geoip] The configured lookup service is not available; check its configuration in the admin panel.');

                return $ipInfo;
            }

            $response = $this->geoip->get($command->ip);

            if (!$response || $response->fake) {
                $this->log->error("Unable to fetch IP information for IP: {$command->ip}", $response ? $response->toJSON() : []);
            }

            if ($response) {
                $data = $response->toJSON();
                $data['address'] = $command->ip;

                $ipInfo = IPInfo::query()->updateOrCreate(
                    ['address' => $command->ip],
                    $data
                );
            }
        }

        return $ipInfo;
    }
}
