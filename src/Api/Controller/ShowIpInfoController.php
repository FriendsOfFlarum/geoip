<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Api\Serializer\IPInfoSerializer;
use FoF\GeoIP\Command\FetchIPInfo;
use FoF\GeoIP\Model\IPInfo;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowIpInfoController extends AbstractShowController
{
    public $serializer = IPInfoSerializer::class;

    public function __construct(protected GeoIP $geoIP, protected Dispatcher $bus)
    {
    }

    /**
     * Get the IP information, either from the database or by performing a lookup.
     *
     * @param ServerRequestInterface $request
     * @param Document               $document
     *
     * @return IPInfo
     */
    public function data(ServerRequestInterface $request, Document $document): IPInfo
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $ip = urldecode(Arr::get($request->getQueryParams(), 'ip'));

        return $this->bus->dispatch(new FetchIPInfo($ip, $actor));
    }
}
