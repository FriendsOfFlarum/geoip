<?php

/*
 * This file is part of fof/geoip.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\GeoIP\Api;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\GeoIP\Concerns\OfflineServiceInterface;
use FoF\GeoIP\Concerns\ServiceInterface;
use FoF\GeoIP\Model\IPInfo;
use FoF\GeoIP\Traits\HandlesGeoIPErrors;
use Psr\Log\LoggerInterface;

class GeoIP
{
    use HandlesGeoIPErrors;

    /**
     * @var array<string, class-string>
     */
    public static array $services = [
        'ipapi'       => Services\IPApi::class,
        'ipapi-pro'   => Services\IPApiPro::class,
        'ipinfo-lite' => Services\IPInfoLite::class,
        'ipdata'      => Services\IPData::class,
        'iplocation'  => Services\IPLocation::class,
        'ipsevenex'   => Services\IPSevenEx::class,
        'maxmind'     => Services\MaxMindDb::class,
    ];

    /**
     * Service pinned by the Services extender, overriding the setting.
     */
    public static ?string $forced = null;

    /**
     * Per-service configuration supplied in code by the Services extender,
     * taking precedence over the equivalent settings.
     *
     * @var array<string, array<string, string|null>>
     */
    public static array $configured = [];

    private string $prefix = 'fof-geoip.services';

    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public static function force(string $service): void
    {
        self::$forced = $service;
    }

    /**
     * @param array<string, string|null> $config
     */
    public static function configure(string $service, array $config): void
    {
        self::$configured[$service] = array_merge(self::$configured[$service] ?? [], $config);
    }

    /**
     * Whether an extender has pinned the service, in which case the admin
     * selector is locked.
     */
    public function isForced(): bool
    {
        return self::$forced !== null;
    }

    /**
     * The resolved service, and the configuration it was built from.
     *
     * Memoized because resolving is not free: the offline driver opens every
     * configured .mmdb file, and a single get() resolves twice — once via
     * isAvailable(). Rebuilding per call measured 0.44ms per lookup against
     * 0.013ms reusing the instance, essentially all of it file opening.
     */
    private ?ServiceInterface $resolved = null;
    private ?string $resolvedKey = null;

    public function getService(): ?ServiceInterface
    {
        $serviceName = $this->getServiceName();
        $service = self::$services[$serviceName] ?? null;

        if (!$service) {
            return null;
        }

        // Keyed on the configuration rather than the name alone, so an admin
        // saving a new path gets a driver reading it rather than the instance
        // built moments earlier.
        $key = $serviceName.'|'.json_encode(self::$configured[$serviceName] ?? []).'|'
            .(is_a($service, Services\MaxMindDb::class, true) ? json_encode($this->databasePaths($serviceName)) : '');

        if ($this->resolved !== null && $this->resolvedKey === $key) {
            return $this->resolved;
        }

        $this->resolvedKey = $key;

        // The offline driver is constructed with its database paths rather
        // than reading them itself, so the extender can supply them in code
        // and settings act as the fallback.
        if (is_a($service, Services\MaxMindDb::class, true)) {
            return $this->resolved = new Services\MaxMindDb($this->databasePaths($serviceName), resolve(LoggerInterface::class));
        }

        return $this->resolved = resolve($service);
    }

    public function getServiceName(): ?string
    {
        return self::$forced ?? $this->settings->get('fof-geoip.service');
    }

    /**
     * The settings the active service declares, for the admin page.
     *
     * @return array<string, array<string, mixed>>
     */
    public function serviceSettings(): array
    {
        $service = $this->getService();

        if ($service === null) {
            return [];
        }

        $settings = $service->settings();

        // Paths pinned by an extender are shown as read-only: editing them
        // would have no effect, since code takes precedence over settings.
        $pinned = array_keys(array_filter(self::$configured[$this->getServiceName()] ?? []));

        foreach ($settings as $key => $definition) {
            foreach ($pinned as $kind) {
                if (str_ends_with($key, ".{$kind}_path")) {
                    $settings[$key]['pinned'] = true;
                }
            }
        }

        return $settings;
    }

    /**
     * A single configuration value for a service: pinned by an extender if
     * present, otherwise the stored setting.
     *
     * Every service reads its configuration through here rather than the
     * settings repository directly, so `configure()` works uniformly — an
     * image can pin an API key exactly as it pins a database path.
     */
    public function config(string $serviceName, string $key): ?string
    {
        $pinned = self::$configured[$serviceName][$key] ?? null;

        if ($pinned !== null && $pinned !== '') {
            return $pinned;
        }

        return $this->settings->get("{$this->prefix}.$serviceName.$key") ?: null;
    }

    /**
     * Resolve the offline driver's database paths: those pinned by an extender
     * first, then the settings.
     *
     * @return array<string, string|null>
     */
    public function databasePaths(string $serviceName): array
    {
        $paths = [];

        $pinned = self::$configured[$serviceName] ?? [];

        foreach (Services\MaxMindDb::KINDS as $kind) {
            // configure() accepts both `country` and `country_path`: the short
            // form reads naturally in an extender, the suffixed form matches
            // the setting key exactly. Either pinned value wins over the
            // setting, so check both before falling back.
            $paths[$kind] = ($pinned["{$kind}_path"] ?? null)
                ?: ($pinned[$kind] ?? null)
                ?: $this->config($serviceName, "{$kind}_path");
        }

        return $paths;
    }

    public function batchSupported(): bool
    {
        $service = $this->getService();

        return $service && $service->batchSupported();
    }

    /**
     * Whether the configured service is in a state to answer lookups.
     *
     * Only services that can inspect their own local state report this — an
     * offline driver knows whether it has a readable database, whereas an HTTP
     * service cannot know it is reachable without making a request, so it is
     * assumed available and failures surface per request as before.
     */
    public function isAvailable(): bool
    {
        $service = $this->getService();

        if ($service === null) {
            return false;
        }

        if ($service instanceof OfflineServiceInterface) {
            return $service->isAvailable();
        }

        return true;
    }

    /**
     * @param string $ip
     *
     * @return ServiceResponse|null
     */
    public function get(string $ip): ?ServiceResponse
    {
        // Guarded rather than calling straight through: a service that is
        // selected but unusable (an offline driver with no readable database)
        // would otherwise miss on every address, and a missing service would
        // fatal outright.
        if (!$this->isAvailable()) {
            return null;
        }

        return $this->getService()->get($ip);
    }

    /**
     * @param array $ips
     *
     * @return ServiceResponse[]
     */
    public function getBatch(array $ips)
    {
        return $this->getService()->getBatch($ips);
    }

    public function getSaved(string $ip): ?IPInfo
    {
        return IPInfo::where('address', $ip)->first();
    }

    protected function checkErrors(): ?ServiceResponse
    {
        $serviceName = $this->settings->get('fof-geoip.service');
        $service = self::$services[$serviceName] ?? null;

        if (!$service) {
            return null;
        }

        $timeKey = "{$this->prefix}.$serviceName.last_error_time";
        $errorKey = "{$this->prefix}.$serviceName.error";
        $lastErrorTime = $this->settings->get($timeKey);

        if ($lastErrorTime && Carbon::createFromTimestamp($lastErrorTime)->isAfter(Carbon::now()->subHour())) {
            return $this->handleGeoIPError($service, $this->settings->get($errorKey));
        } elseif ($lastErrorTime) {
            $this->settings->delete($timeKey);
            $this->settings->delete($errorKey);
        }

        return null;
    }

    public static function setError(string $service, string $error): ServiceResponse
    {
        $settings = resolve('flarum.settings');

        $settings->set("fof-geoip.services.$service.last_error_time", time());
        $settings->set("fof-geoip.services.$service.error", $error);

        return self::getFakeResponse($service, $error);
    }

    protected static function getFakeResponse(string $service, string $error): ServiceResponse
    {
        return (new ServiceResponse($service, true))
            ->setError($error);
    }
}
