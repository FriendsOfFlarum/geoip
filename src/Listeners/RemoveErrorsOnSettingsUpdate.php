<?php


namespace FoF\GeoIP\Listeners;


use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;

class RemoveErrorsOnSettingsUpdate
{
    /**
     * @var SettingsRepositoryInterface
     */
    private $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Saving $event) {
        foreach ($event->settings as $key => $value) {
            if (!Str::startsWith($key, 'fof-geoip.service')) {
                continue;
            }

            $service = $value;

            if ($key !== 'fof-geoip.service') {
                $matches = null;
                preg_match('/fof-geoip\.services\.(.+?)\./m', $key, $matches);

                $service = $matches[1] ?? $value;
            }

            $this->settings->delete("fof-geoip.services.$service.error");
            $this->settings->delete("fof-geoip.services.$service.last_error_time");
        }
    }
}
