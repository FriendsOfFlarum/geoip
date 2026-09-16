<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Console;

use Flarum\Database\AbstractModel;
use Flarum\Http\AccessToken;
use Flarum\Post\Post;
use FoF\Drafts\Draft;
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Command\FetchIPInfo;
use FoF\GeoIP\Command\FetchIPInfoBatch;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;

class LookupUnknownIPsCommand extends Command
{
    /**
     * An array of models which hold IP addresses. The value is the column name.
     */
    private array $ipModels = [
        AccessToken::class => 'last_ip_address',
        Post::class        => 'ip_address',
        Draft::class       => 'ip_address',
    ];

    protected $signature = 'fof:geoip:lookup {--force}';

    protected $description = 'Look up IP addresses which have not been looked up before.';

    public function __construct(protected GeoIP $geoIP, protected Dispatcher $bus)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        foreach ($this->ipModels as $model => $column) {
            if (!class_exists($model)) {
                continue;
            }

            /** @var class-string<AbstractModel> $model */
            $query = $model::query();

            // The class can exist while its table does not: fof/drafts ships
            // the Draft model, but an installation that has the package
            // without the extension enabled has never run its migrations.
            if (!$query->getModel()->getConnection()->getSchemaBuilder()->hasTable($query->getModel()->getTable())) {
                continue;
            }

            $force = (bool) $this->option('force');

            // Only the address is read from the result, so select it alone
            // and take distinct values — each address needs looking up once,
            // however many rows carry it. (Selecting `id` alongside a GROUP BY
            // on the address is invalid SQL: PostgreSQL rejects it, while
            // MySQL and SQLite silently allow it.)
            //
            // Rows without an address are never lookupable — access tokens and
            // imported posts routinely have none. --force skips the "not seen
            // before" filter below, but must not skip this: a null address
            // reaching FetchIPInfo aborts the whole run.
            $query->select($column)->distinct()->whereNotNull($column);

            if ($force) {
                $this->info("Forcing lookup for {$model}");
            } else {
                $query->whereNotIn($column, function ($query) use ($column) {
                    $query->select('address')
                        ->from('ip_info')
                        ->whereColumn('address', $column);
                });
            }

            if ($query->count() > 0) {
                $this->info("Looking up IP data for {$model}");

                $this->output->progressStart($query->count());
                $chunkSize = 100;

                // chunk(), not chunkById(): the query selects only the
                // address, so there is no id to page by. Ordering keeps the
                // pages stable, and nothing is mutated during the walk.
                $query
                    ->orderBy($column)
                    ->chunk($chunkSize, function ($models) use ($column, $chunkSize, $force) {
                        if ($this->geoIP->batchSupported()) {
                            $ips = $models->pluck($column)->toArray();
                            $count = count($ips);
                            $this->bus->dispatch(new FetchIPInfoBatch(ips: $ips, refresh: $force));
                            $this->output->progressAdvance($chunkSize === $count ? $chunkSize : $count);
                        } else {
                            $models->each(function ($model) use ($column, $force) {
                                $this->info("Looking up IP data for {$model->$column}");
                                $this->bus->dispatch(new FetchIPInfo(ip: $model->$column, refresh: $force));
                                $this->output->progressAdvance();
                            });
                        }
                    });

                $this->output->progressFinish();
            } else {
                $this->info("Nothing to look up for {$model}");
            }
        }
    }
}
