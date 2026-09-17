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
use FoF\GeoIP\Api\GeoIP;
use FoF\GeoIP\Command\FetchIPInfo;
use FoF\GeoIP\Command\FetchIPInfoBatch;
use FoF\GeoIP\Model\IPInfo;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Console\Output\OutputInterface;

class LookupUnknownIPsCommand extends Command
{
    /**
     * Models which hold IP addresses, mapped to the column holding them.
     *
     * Each is optional: the class may be absent (package not installed) or
     * present without its table (installed but never enabled). Both are
     * checked before the model is touched.
     *
     * The audit log is worth scanning even though its rows are not posts — it
     * records failed logins and blocked registrations, which are the addresses
     * a moderator most wants placed, and which never produce a post.
     */
    private array $ipModels = [
        AccessToken::class            => 'last_ip_address',
        Post::class                   => 'ip_address',
        \FoF\Drafts\Draft::class      => 'ip_address',
        \Flarum\Audit\AuditLog::class => 'ip_address',
    ];

    protected $signature = 'fof:geoip:lookup {--force : Re-look-up every address, including those already stored}';

    protected $description = 'Look up IP addresses which have not been looked up before.';

    public function __construct(protected GeoIP $geoIP, protected Dispatcher $bus)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->geoIP->isAvailable()) {
            $this->error('The configured lookup service is not available. Check its configuration in the admin panel.');

            return Command::FAILURE;
        }

        $force = (bool) $this->option('force');
        $addresses = $this->collectAddresses($force);

        if ($addresses === []) {
            $this->info('Nothing to look up.');

            return Command::SUCCESS;
        }

        $this->lookup($addresses, $force);

        return Command::SUCCESS;
    }

    /**
     * Gather every distinct address worth looking up, across all sources.
     *
     * Collected up front rather than per model, so an address appearing in
     * several places is looked up once and the progress bar is sized from the
     * real total instead of a per-model count queried twice.
     *
     * @return string[]
     */
    private function collectAddresses(bool $force): array
    {
        $addresses = [];

        foreach ($this->ipModels as $model => $column) {
            $query = $this->queryFor($model, $column, $force);

            if ($query === null) {
                continue;
            }

            $found = $query->pluck($column)->all();

            $this->line(
                sprintf('  %s: %d', class_basename($model), count($found)),
                null,
                OutputInterface::VERBOSITY_VERBOSE
            );

            $addresses = array_merge($addresses, $found);
        }

        return array_values(array_unique($addresses));
    }

    /**
     * Build the address query for a model, or null when it cannot be scanned.
     */
    private function queryFor(string $model, string $column, bool $force): ?Builder
    {
        if (!class_exists($model)) {
            return null;
        }

        /** @var class-string<AbstractModel> $model */
        $query = $model::query();

        // The class can exist while its table does not: fof/drafts and
        // flarum/audit both ship models an installation may have without ever
        // having run their migrations.
        if (!$query->getModel()->getConnection()->getSchemaBuilder()->hasTable($query->getModel()->getTable())) {
            return null;
        }

        // Only the address is read, so select it alone and take distinct
        // values. (Selecting `id` alongside a GROUP BY on the address is
        // invalid SQL: PostgreSQL rejects it, MySQL and SQLite allow it.)
        //
        // Rows without an address are never lookupable — access tokens and CLI
        // audit entries routinely have none — and --force must not skip this
        // filter: a null address reaching FetchIPInfo aborts the run.
        $query->select($column)->distinct()->whereNotNull($column);

        if (!$force) {
            $query->whereNotIn($column, function ($query) use ($column) {
                $query->select('address')
                    ->from('ip_info')
                    ->whereColumn('address', $column);
            });
        }

        return $query;
    }

    /**
     * @param string[] $addresses
     */
    private function lookup(array $addresses, bool $force): void
    {
        $total = count($addresses);

        $this->info(sprintf(
            '%s %d address%s.',
            $force ? 'Refreshing' : 'Looking up',
            $total,
            $total === 1 ? '' : 'es'
        ));

        // The bar owns its line and redraws in place, so nothing else may
        // write while it is running. Per-address detail therefore replaces the
        // bar under -v rather than fighting with it.
        $verbose = $this->output->isVerbose();

        if (!$verbose) {
            $this->output->progressStart($total);
        }

        foreach (array_chunk($addresses, 100) as $chunk) {
            if ($this->geoIP->batchSupported()) {
                $this->bus->dispatch(new FetchIPInfoBatch(ips: $chunk, refresh: $force));

                if ($verbose) {
                    foreach ($chunk as $ip) {
                        $this->line("  $ip");
                    }
                } else {
                    $this->output->progressAdvance(count($chunk));
                }

                continue;
            }

            foreach ($chunk as $ip) {
                if ($verbose) {
                    $this->line("  $ip");
                }

                $this->bus->dispatch(new FetchIPInfo(ip: $ip, refresh: $force));

                if (!$verbose) {
                    $this->output->progressAdvance();
                }
            }
        }

        if (!$verbose) {
            $this->output->progressFinish();
        }

        $this->report($addresses);
    }

    /**
     * Say what actually happened.
     *
     * Counted from the stored records rather than from what was dispatched: an
     * address the data source does not cover resolves to nothing, and an
     * operator should be told how many rather than inferring it from a
     * progress bar that reached 100%.
     *
     * @param string[] $addresses
     */
    private function report(array $addresses): void
    {
        $resolved = IPInfo::query()->whereIn('address', $addresses)->count();
        $unresolved = count($addresses) - $resolved;

        $this->info(sprintf('%d resolved.', $resolved));

        if ($unresolved > 0) {
            $this->warn(sprintf(
                '%d unresolved: not present in the configured data source. Private, reserved and unallocated addresses are expected here.',
                $unresolved
            ));
        }
    }
}
