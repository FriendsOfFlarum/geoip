<?php

namespace FoF\GeoIP\Content;

use Flarum\Frontend\Document;
use FoF\GeoIP\Api\GeoIP;

class AdminPayload
{
    public function __invoke(Document $document)
    {
        $document->payload['fof-geoip.services'] = array_keys(GeoIP::$services);



    }
}