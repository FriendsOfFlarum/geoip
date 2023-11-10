<?php

namespace FoF\GeoIP\Api\Services;

class IPApiPro extends IPApi
{
    protected $host = 'https://ip-api.com';
    protected $settingPrefix = 'fof-geoip.services.ipapi-pro';

    protected function requiresApiKey(): bool
    {
        return true;
    }

    public function isRateLimited(): bool
    {
        return false;
    }
}
