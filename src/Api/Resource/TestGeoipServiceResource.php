<?php

namespace FoF\GeoIP\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractResource<object>
 */
class TestGeoipServiceResource extends Resource\AbstractResource implements 
{
    public function type(): string
    {
        return 'geoip-test';
    }

    public function endpoints(): array
    {
        return [
        ];
    }

    public function fields(): array
    {
        return [

            /**
             * @todo migrate logic from old serializer and controllers to this API Resource.
             * @see https://docs.flarum.org/2.x/extend/api#api-resources
             */

            // Example:
            Schema\Str::make('name')
                ->requiredOnCreate()
                ->minLength(3)
                ->maxLength(255)
                ->writable(),


        ];
    }

    public function getId(object $model, OriginalContext $context): string
    {
        return $model->id;
    }
}
