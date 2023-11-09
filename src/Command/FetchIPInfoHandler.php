<?php

namespace FoF\GeoIP\Command;

use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Model\IPInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FetchIPInfoHandler
{
    public function __construct(
        protected GeoIP $geoip
    ) {}
    
    public function handle(FetchIPInfo $command): IPInfo
    {
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
