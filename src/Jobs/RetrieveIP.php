<?php

namespace FoF\GeoIP\Jobs;

use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\IPInfo;
use Illuminate\Cache\Repository;
use Illuminate\Support\Arr;

class RetrieveIP extends AbstractJob
{
    // Keep track of IPs we're retrieving & have already retrieved to avoid duplicate requests in the same request/queue worker
    public static array $queued = [];
    public static array $retrieving = [];
    public static array $retrieved = [];

    public function __construct(protected string $ip) {
        self::$queued[] = $ip;
    }

    public function handle(GeoIP $geoIP, Repository $cache): void
    {
        $ip = $this->ip;
        $cacheKey = "fof-geoip.retrieving.$ip";

        // Return if 1) no IP, 2) already retrieving this IP, 3) already retrieved this IP, 4) already cached this IP
        if (!$ip || self::isRetrieving($ip) || Arr::has(static::$retrieved, $ip) || $cache->has($cacheKey)) {
            return;
        }

        // Add to retrieving list and cache (don't attempt again for 1 hour if job fails)
        // Errors from the API do not count as job failures.
        static::$retrieving[] = $ip;
        $cache->add($cacheKey, true, 60 * 60);

        $response = $geoIP->get($ip);

        if ($response) {
            $ipInfo = IPInfo::where('address', $ip)->first();

            if (!$ipInfo) {
                $ipInfo = new IPInfo();
                $ipInfo->address = $ip;
                $ipInfo->fill($response->toJson());

                // If response is fake, it means an error occurred that was logged to the admin dashboard.
                // We don't want to save fake responses.
                if (!$response->fake) {
                    $ipInfo->save();
                }
            }

            if (!$response->fake) {
                // If using sync queue driver, this will be immediately available.
                // If using another driver (eg. redis), it will remember this IP has been retrieved until the process ends.
                static::$retrieved[$ip] = $ipInfo;
            }
        }

        // Only remove from cache if we didn't get a fake response
        if (!$response || !$response->fake) {
            // Remove from retrieving list and cache
            static::$retrieving = array_diff(static::$retrieving, [$ip]);
            $cache->forget("fof-geoip.retrieving.$ip");
        }
    }

    public static function isRetrieving($ip): bool
    {
        return in_array($ip, static::$retrieving);
    }

    public static function isQueued($ip): bool
    {
        return in_array($ip, static::$queued) || self::isRetrieving($ip);
    }
}
