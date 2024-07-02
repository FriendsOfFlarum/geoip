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
use Illuminate\Support\Arr;

class FetchIPInfoBatchHandler
{
    public function __construct(
        protected GeoIP $geoip,
        protected GeoIPRepository $repository
    ) {
    }

    public function handle(FetchIPInfoBatch $command)
    {
        // remove any duplicates or invalid addresses from the ips array
        $command->ips = array_unique(array_filter($command->ips, fn ($ip) => $this->repository->isValidIP($ip)));

        // query the IPInfo model for all the ips in the command
        // if the ip doesn't exist, create a new IPInfo model

        $ipInfos = IPInfo::query()->whereIn('address', $command->ips)->get();

        // for any ips that don't exist, create a new IPInfo model, create an array of ips to query and call the getBatch() method

        $ipsToQuery = Arr::except($command->ips, $ipInfos->pluck('address')->toArray());

        if (count($ipsToQuery) > 0) {
            $responses = $this->geoip->getBatch($ipsToQuery);

            foreach ($responses as $response) {
                $ip = $response->getIP();
                $ipInfo = IPInfo::query()->firstOrNew(['address' => $ip]);
                $ipInfo->address = $ip;
                $ipInfo->fill($response->toJSON());
                $ipInfo->save();

                // add the new IPInfo model to the collection
                $ipInfos->push($ipInfo);
            }
        }

        return $ipInfos;
    }
}
