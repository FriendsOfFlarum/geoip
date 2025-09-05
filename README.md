# GeoIP by FriendsOfFlarum

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/geoip.svg)](https://packagist.org/packages/fof/geoip) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)  [![Donate](https://img.shields.io/badge/donate-datitisev-important.svg)](https://datitisev.me/donate)

A [Flarum](http://flarum.org) extension.

## IP Geolocation & Security Insights for Flarum

Provide moderators with powerful IP geolocation tools for better forum management, while giving users visibility into their account access patterns and security. Moderators get comprehensive IP insights for moderation decisions, while users can monitor where their accounts are being accessed from for enhanced security awareness.

### 🌎 Key Features
- **Location Insights**: Enable moderators to identify the country and region of users.
- **Interactive Mapping**: Let moderators visualize user locations with an integrated map view.
- **Threat Detection**: Equip moderators with the ability to highlight potentially malicious IP addresses through threat level indicators. (Via supported IP location data providers)

### 🔌 Supported IP Data Providers

GeoIP supports multiple IP lookup services, each with different features, rate limits, and data coverage:

**Default Provider**: The extension comes pre-configured with **IP-API** as the default provider since it requires no API key and allows you to get started immediately with up to 45 lookups per minute.

#### **IPData** (`ipdata`)
- **Service**: [https://ipdata.co](https://ipdata.co)
- **Free Tier**: Up to 1,500 lookups daily
- **Paid Plans**: Available for higher usage limits
- **Requirements**: API key required
- **Data Provided**:
  - ✅ Country Code
  - ✅ Zip/Postal Code
  - ✅ Latitude/Longitude
  - ✅ ISP
  - ✅ Organization
  - ✅ ASN (Autonomous System Number)
  - ✅ Mobile/Cellular Detection
  - ✅ Threat Level Detection
  - ✅ Threat Type Classification (attacker/abuser)

#### **IP-API** (`ipapi`) - *Default*
- **Service**: [http://ip-api.com](http://ip-api.com)
- **Free Tier**: Up to 45 lookups per minute
- **Rate Limiting**: Requests exceeding the limit are automatically queued and processed when the limit resets
- **Batch Support**: Yes (up to 15 batch requests per minute)
- **Automatic Retry**: Built-in retry logic for failed requests
- **Requirements**: No API key needed
- **Data Provided**:
  - ✅ Country Code
  - ✅ Zip/Postal Code
  - ✅ Latitude/Longitude
  - ✅ ISP
  - ✅ Organization
  - ✅ ASN (Autonomous System Number)
  - ✅ Mobile/Cellular Detection

#### **IP-API Pro** (`ipapi-pro`)
- **Service**: [https://members.ip-api.com/#pricing](https://members.ip-api.com/#pricing)
- **Usage**: Unlimited lookups (paid service)
- **Requirements**: API key required
- **Data Provided**: Same as IP-API (inherits all features)
  - ✅ Country Code
  - ✅ Zip/Postal Code
  - ✅ Latitude/Longitude
  - ✅ ISP
  - ✅ Organization
  - ✅ ASN (Autonomous System Number)
  - ✅ Mobile/Cellular Detection

#### **IP Location** (`iplocation`)
- **Service**: [https://www.iplocation.net/](https://www.iplocation.net/)
- **Rate Limits**: Unknown/undocumented
- **Requirements**: No API key needed
- **Data Provided** (Limited):
  - ✅ Country Code
  - ✅ ISP
  - ❌ No zip code, coordinates, or threat data

#### **7x Geolocation API** (`ipsevenex`)
- **Service**: [https://7x.ax](https://7x.ax)
- **Free Tier**: Up to 20 requests per minute with API key
- **Paid Plans**: Available for higher usage limits
- **Requirements**: API key required (free registration available)
- **Data Provided**:
  - ✅ Country Code
  - ✅ Zip/Postal Code
  - ✅ Latitude/Longitude
  - ✅ ISP
  - ✅ Organization

Choose the provider that best fits your forum's traffic volume, data requirements, and budget.

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

The IP lookup can be time consuming, so the lookup of an unknown IP address is dispatched in a job, if you have a queue running this will run on a worker thread, rather than the main thread.

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
- Consider upgrading to a paid plan for higher limits

**No country flags showing**
1. Ensure "Show country flag for each post" is enabled in settings
2. Users must opt-in via their preferences (unless admin permission overrides this)
3. Check that the IP lookup returned valid country data

**Queue not processing**
- Ensure your queue worker is running: `php flarum queue:work`
- Check queue configuration in your hosting environment

### ⚡ Performance Considerations

- **Queue Processing**: IP lookups are processed in background jobs to avoid blocking page loads
- **Caching**: Results are cached to avoid repeated API calls for the same IP
- **Rate Limiting**: Built-in rate limiting prevents API quota exhaustion
- **Batch Processing**: Some providers support batch lookups for better efficiency

For high-traffic forums, consider:
- Using a paid provider with higher rate limits
- Ensuring your queue worker is properly configured
- Monitoring your API usage through provider dashboards

### 📊 Data Storage

- IP geolocation data is stored locally in your database after lookup
- Data includes: country, coordinates, ISP, organization, and threat information (where available)
- No personal user data is sent to IP lookup providers
- Only IP addresses are transmitted for geolocation lookup

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

[![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate) [![GitHub](https://img.shields.io/badge/donate-datitisev-ea4aaa?style=for-the-badge&logo=github)](https://datitisev.me/donate/github)

- [Packagist](https://packagist.org/packages/fof/geoip)
- [GitHub](https://github.com/FriendsOfFlarum/geoip)

An extension by [FriendsOfFlarum](https://github.com/FriendsOfFlarum).
