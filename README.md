# GeoIP by FriendsOfFlarum

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/geoip.svg)](https://packagist.org/packages/fof/geoip) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)  [![Donate](https://img.shields.io/badge/donate-datitisev-important.svg)](https://datitisev.me/donate)

A [Flarum](http://flarum.org) extension.

## IP Geolocation & Security Insights for Flarum

Provide moderators with powerful IP geolocation tools for better forum management, while giving users visibility into their account access patterns and security. Moderators get comprehensive IP insights for moderation decisions, while users can monitor where their accounts are being accessed from for enhanced security awareness.

### 🌎 Key Features
- **Location Insights**: Enable moderators to identify the country and region of users.
- **Interactive Mapping**: Let moderators visualize user locations with an integrated map view.
- **Threat Detection**: Equip moderators with the ability to highlight potentially malicious IP addresses through threat level indicators. (Via supported IP location data providers)
- **Offline Lookups**: Resolve addresses from local MaxMind or DB-IP databases, with no rate limits and no IP address leaving your server.

### 🔌 Supported IP Data Providers

GeoIP supports multiple lookup services, each with different features, rate limits and data coverage. One is active at a time, chosen in the admin settings.

**Default**: **IP-API**, which needs no API key and allows up to 45 lookups per minute.

| Provider | Key | API key | Country | City / Region | Postal | Coords | ISP / Org | ASN | Mobile | Threat |
|---|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Offline databases | `maxmind` | — | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ | ❌ | ❌ |
| IP-API | `ipapi` | — | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| IP-API Pro | `ipapi-pro` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| IPData | `ipdata` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 7x Geolocation | `ipsevenex` | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| IPInfo Lite | `ipinfo-lite` | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| IP Location | `iplocation` | — | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |

⚠️ Postal codes are available from MaxMind's City databases but **not** from DB-IP's Lite editions.

#### Offline databases — MaxMind / DB-IP (`maxmind`)

Reads local MaxMind-format (`.mmdb`) databases instead of calling a remote API. Lookups are instant, unlimited, and no IP address ever leaves your server — but you install and update the database files yourself.

Works with MaxMind's GeoLite2/GeoIP2 and DB-IP's Lite editions; both publish compatible formats.

- **Requirements**: at least one `.mmdb` file. The PHP `maxminddb` extension is optional and makes lookups roughly 5× faster; the bundled pure-PHP reader is used when it is absent.
- **Databases**: all three are optional and independent.
  - **Country** — country code. Enough on its own for country flags (~8 MB).
  - **City** — the above plus city, region and coordinates (~120 MB).
  - **ASN** — autonomous system number and organisation (~9 MB).
- **Not available offline**: threat data and mobile/cellular detection. Neither vendor ships them.

Configure the paths in the admin settings, or pin them in `extend.php` (see [Configuring in code](#configuring-in-code)). The settings page shows each database's type and build date, and flags one as out of date once it is **more than 90 days old** — roughly three missed editions, since both vendors publish monthly. A stale database still works; its allocations are just increasingly likely to be wrong.

##### Obtaining the databases

These are examples, not something the extension runs for you. Adapt them to your host and schedule them however you prefer — monthly is typical, since both vendors publish new editions each month.

**DB-IP Lite** — no account required, [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/):

```sh
mkdir -p /usr/share/GeoIP && cd /usr/share/GeoIP
MONTH=$(date +%Y-%m)

for DB in country city asn; do
  # Download to a temp file in the SAME directory, then rename into place.
  # rename(2) on one filesystem is atomic, so a lookup happening mid-update
  # never sees a half-written file: readers with the old file open keep it
  # until they close, new opens get the new one. Never write straight over a
  # live .mmdb — `curl | gunzip > file` truncates it and can corrupt a
  # concurrent read.
  tmp=$(mktemp "dbip-$DB-lite.XXXXXX.mmdb")
  if curl -fsSL "https://download.db-ip.com/free/dbip-$DB-lite-$MONTH.mmdb.gz" \
       | gunzip > "$tmp"; then
    mv -f "$tmp" "dbip-$DB-lite.mmdb"        # atomic swap
  else
    rm -f "$tmp"                             # keep the existing file on failure
  fi
done
```

The filenames stay the same every month, so whatever you point the settings (or `extend.php`) at keeps working across updates.

**MaxMind GeoLite2** — free, but requires an account and a licence key. Debian and Ubuntu package the official updater:

```sh
apt install geoipupdate
# configure /etc/GeoIP.conf with your AccountID and LicenseKey, then:
geoipupdate
```

If you cannot write to `/usr/share/GeoIP` — shared hosting, or a container running as an unprivileged user — put the files anywhere readable, such as `storage/geoip/` inside your Flarum install, and point the settings at that path. Keep the temp file and the final file on the same filesystem (the example above puts both in the destination directory) so the `mv` stays an atomic rename rather than a copy.

> **Attribution**: DB-IP's Lite databases are CC BY 4.0 and **require** visible attribution wherever you surface results derived from them, for example `IP Geolocation by <a href="https://db-ip.com">DB-IP</a>`. MaxMind's GeoLite2 licence has a similar requirement. Check the terms of whichever databases you install. This attribution is shown in the map modal whenever DB-IP data is displayed.

#### IPData (`ipdata`)

- **Service**: [https://ipdata.co](https://ipdata.co)
- **Free tier**: 1,500 lookups daily; paid plans for more
- **Requirements**: API key
- The only provider offering **threat level and threat type** (attacker / abuser) classification.

#### IP-API (`ipapi`) — *default*

- **Service**: [http://ip-api.com](http://ip-api.com)
- **Free tier**: 45 lookups per minute
- **Requirements**: none
- **Batch support**: yes, up to 15 batch requests per minute
- Requests exceeding the limit are queued and retried once the window resets.

#### IP-API Pro (`ipapi-pro`)

- **Service**: [https://members.ip-api.com/#pricing](https://members.ip-api.com/#pricing)
- **Usage**: unlimited (paid)
- **Requirements**: API key
- Same data as IP-API, without the rate limit.

#### 7x Geolocation API (`ipsevenex`)

- **Service**: [https://7x.ax](https://7x.ax)
- **Free tier**: 20 requests per minute; paid plans for more
- **Requirements**: API key (free registration)

#### IPInfo Lite (`ipinfo-lite`)

- **Service**: [https://ipinfo.io](https://ipinfo.io)
- **Usage**: no rate limiting
- **Requirements**: API key (free registration)
- Country and network data only — no city, coordinates or postal code.

#### IP Location (`iplocation`)

- **Service**: [https://www.iplocation.net/](https://www.iplocation.net/)
- **Rate limits**: undocumented
- **Requirements**: none
- Country and ISP only.

### Configuring in code

Everything configurable in the admin panel can instead be pinned in your `extend.php`. This suits Docker images and managed deployments, where the correct provider is a property of the environment rather than something an administrator should have to set — or be able to break. Pinned values take precedence over stored settings and survive a settings reset.

```php
use FoF\GeoIP\Extend\Services;

return [
    (new Services())
        ->force('maxmind')
        ->configure('maxmind', [
            'country' => '/usr/share/GeoIP/dbip-country-lite.mmdb',
            'city'    => '/usr/share/GeoIP/dbip-city-lite.mmdb',
            'asn'     => '/usr/share/GeoIP/dbip-asn-lite.mmdb',
        ]),
];
```

The same works for the hosted providers:

```php
(new Services())
    ->force('ipdata')
    ->configure('ipdata', ['access_key' => 'your-api-key']),
```

| Method | Purpose |
|---|---|
| `force(string $service)` | Use this provider regardless of the setting. The admin selector is locked and explains why. |
| `configure(string $service, array $config)` | Supply configuration in code. Keys match the provider's settings: `access_key` for hosted providers, `country` / `city` / `asn` for offline databases. |
| `register(string $name, string $class)` | Register an additional provider implementing `FoF\GeoIP\Concerns\ServiceInterface`. |

Anything pinned is shown read-only in the admin panel, so the interface reflects what is actually in effect.

### 🔐 Permissions

The extension provides the following permission:
- **Always display the country of the IP address** - Allows users to always see country flags, regardless of the post author's privacy settings

By default, only administrators and moderators can see IP addresses and detailed geolocation information.

### 👤 User Privacy Controls

Users have control over their location visibility:
- **Show country flag**: Users can opt-in to display their country flag on posts via their user preferences
- **IP addresses**: Only visible to administrators and moderators
- **Detailed location data**: Only accessible to administrators and moderators through the IP info modal

### Screenshots
##### Redesigned meta info (visible to admins/mods)
![image](https://user-images.githubusercontent.com/16573496/269216977-b8814964-dfe7-4af9-b519-628506fbc109.png)

##### Integration with session management (visible to own profile)
![image](https://user-images.githubusercontent.com/16573496/269137486-b13008fa-a47b-4909-9e9e-d5d2eaa180d4.png)

##### Information modal with location map
![image](https://user-images.githubusercontent.com/16573496/269137411-ae7657f1-38b5-46ba-9bd7-df802696a882.png)

### CLI Usage

The following CLI commands are provided:

#### `lookup`

Although IP addresses will be looked up when they are requested, this command will lookup all IP's that do not already have an entry in the `ip_info` table, using the currently selected provider.

```sh
php flarum fof:geoip:lookup
```

#### `lookup --force`

You may also force a refresh of IP data using the currently selected provider.

```sh
php flarum fof:geoip:lookup --force
```

### Queue offloading

Lookups against a hosted provider can be slow, so an unknown IP address is dispatched as a job; with a queue worker running it is handled off the main thread.

This does not apply to the offline databases, where a lookup is a local file read measured in microseconds — queueing one would cost far more than performing it.

All IP address lookup jobs are dispatched to the `default` queue by default. If you have multiple queues, you can specify which queue to use for these jobs in your `extend.php`:

```
FoF\GeoIP\Jobs\RetrieveIP::$onQueue = 'my-other-queue';
```

### Testing Your Configuration

The extension includes a built-in service tester in the admin settings interface. After configuring your chosen IP lookup provider:

1. **Save your settings first** - The tester uses your currently saved configuration
2. **Navigate to the test section** - Located at the bottom of the GeoIP settings page
3. **Enter an IP address** - Use any valid IPv4 or IPv6 address (defaults to 8.8.8.8)
4. **Click "Test Service"** - This will make a real request to your configured provider

The test results will show:
- **Service response status** - Success or error indication
- **Response time** - How long the lookup took
- **HTTP status code** - The actual HTTP response code from the service
- **Processed data** - The clean, formatted IP information
- **Raw response details** - Complete HTTP headers and response body for debugging
- **Request details** - The exact URL and options used for the request

This testing feature is invaluable for:
- Verifying API keys are working correctly
- Checking service availability and response times
- Debugging configuration issues
- Understanding what data your chosen provider returns

### 🔧 Troubleshooting

#### Common Issues

**IP lookups not working**
1. Check your service configuration in the admin panel
2. Use the built-in service tester to verify your setup
3. Ensure your API key is valid (for services that require one)
4. Check the Flarum logs for error messages

**Rate limit exceeded**
- IP-API: Requests are automatically queued, wait for the next minute
- IPData: Check your daily quota usage
- Consider upgrading to a paid plan for higher limits, or switching to the offline databases, which have no limits

**Offline databases not working**
1. The settings page lists each database with its type and build date — a path that is missing, unreadable or not a valid `.mmdb` file is reported there
2. A banner appears when the selected provider cannot answer lookups at all
3. Check the file is readable by the web server user
4. Private and reserved addresses (`192.168.x.x`, `127.0.0.1`) are absent from every database by design, and are recorded as such rather than looked up repeatedly

**No country flags showing**
1. Ensure "Show country flag for each post" is enabled in settings
2. Users must opt-in via their preferences (unless admin permission overrides this)
3. Check that the IP lookup returned valid country data

**Queue not processing**
- Ensure your queue worker is running: `php flarum queue:work`
- Check queue configuration in your hosting environment

### ⚡ Performance Considerations

- **Queue Processing**: lookups against hosted providers run in background jobs to avoid blocking page loads
- **Caching**: results are stored locally, so an address is looked up once
- **Rate Limiting**: built-in rate limiting prevents API quota exhaustion
- **Batch Processing**: some providers support batch lookups
- **Eager Loading**: IP data is loaded alongside posts and audit log entries in a single query, and ships with the page rather than being fetched per row

For high-traffic forums, consider:
- The offline databases, which have no rate limits, no per-lookup latency and no external calls
- Using a paid provider with higher rate limits
- Ensuring your queue worker is properly configured
- Monitoring your API usage through provider dashboards

### 📊 Data Storage

- IP geolocation data is stored locally in your database after lookup
- Data includes: country, city, region, coordinates, ISP, organization, and threat information (where the provider supplies it)
- No personal user data is sent to IP lookup providers
- Only IP addresses are transmitted for geolocation lookup — and with the offline databases, nothing is transmitted at all

### Installation

Install manually with composer:

```sh
composer require fof/geoip:"*"
```

### Updating

```sh
composer update fof/geoip
php flarum cache:clear
```

### Links

[![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)

- [Packagist](https://packagist.org/packages/fof/geoip)
- [GitHub](https://github.com/FriendsOfFlarum/geoip)

An extension by [FriendsOfFlarum](https://github.com/FriendsOfFlarum).
