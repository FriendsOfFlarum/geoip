<?php


namespace FoF\GeoIP\Api;

interface ServiceInterface
{
    public function get(string $ip);
}
