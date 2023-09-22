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
use FoF\GeoIP\IPInfo;
use FoF\GeoIP\IPInfoSerializer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowIpInfoController extends AbstractShowController
{
    public $serializer = IPInfoSerializer::class;

    protected $geoIP;

    public function __construct(GeoIP $geoIP)
    {
        $this->geoIP = $geoIP;
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
        RequestUtil::getActor($request)->assertRegistered();

        $ip = urldecode(Arr::get($request->getQueryParams(), 'ip'));

        $ipInfo = IPInfo::query()->where('address', $ip)->first();

        if (!$ipInfo) {
            $ipInfo = $this->lookup($ip);
            $this->saveIpInfo($ip, $ipInfo);
        }

        return $ipInfo;
    }

    /**
     * Lookup IP information using the GeoIP service.
     *
     * @param string $ip
     *
     * @throws ModelNotFoundException
     *
     * @return array
     */
    protected function lookup(string $ip): array
    {
        $response = $this->geoIP->get($ip);

        if (!$response || $response->fake) {
            throw new ModelNotFoundException("Unable to fetch IP information for IP: {$ip}");
        }

        return $response->toJson();
    }

    /**
     * Save the IP information to the database.
     *
     * @param string $ip
     * @param array  $data
     *
     * @return IPInfo
     */
    protected function saveIpInfo(string $ip, array $data): IPInfo
    {
        $ipInfo = new IPInfo();
        $ipInfo->address = $ip;
        $ipInfo->fill($data);
        $ipInfo->save();

        return $ipInfo;
    }
}
