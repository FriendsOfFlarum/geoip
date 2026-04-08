<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\User\Exception\PermissionDeniedException;
use FoF\GeoIP\Command\FetchIPInfo;
use FoF\GeoIP\Model\IPInfo;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractDatabaseResource<IPInfo>
 */
class IPInfoResource extends Resource\AbstractDatabaseResource
{
    public function __construct(protected Dispatcher $bus)
    {
    }

    public function type(): string
    {
        return 'ip_info';
    }

    public function model(): string
    {
        return IPInfo::class;
    }

    public function getId(object $model, OriginalContext $context): string
    {
        /** @var IPInfo $model */
        return hash('sha256', (string) $model->address);
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function find(string $id, OriginalContext $context): ?object
    {
        $actor = $context->getActor();

        if (!$actor->exists) {
            throw new PermissionDeniedException();
        }

        // The ID is the IP address (URL encoded)
        $ip = urldecode($id);

        // Reject invalid IPs to prevent abuse and unnecessary external API calls
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        // Try to find existing record first
        $ipInfo = IPInfo::query()->where('address', $ip)->first();

        if ($ipInfo) {
            return $ipInfo;
        }

        // If not found, fetch from external service
        return $this->bus->dispatch(new FetchIPInfo($ip, $actor));
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make()
                ->authenticated(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('countryCode')
                ->visible(),

            // Full details - only for users who can view IPs
            Schema\Str::make('ip')
                ->property('address')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('zipCode')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('latitude')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('longitude')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('isp')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('organization')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('as')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Boolean::make('mobile')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('threatLevel')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('threatType')
                ->property('threat_types')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('error')
                ->nullable()
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\Str::make('dataProvider')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\DateTime::make('createdAt')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
            Schema\DateTime::make('updatedAt')
                ->visible(fn (IPInfo $ipInfo, Context $context) => $context->getActor()->can('discussion.viewIpsPosts')),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
            SortColumn::make('updatedAt'),
        ];
    }
}
