# GeoIP by FriendsOfFlarum

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/geoip.svg)](https://packagist.org/packages/fof/geoip) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)  [![Donate](https://img.shields.io/badge/donate-datitisev-important.svg)](https://datitisev.me/donate)

A [Flarum](http://flarum.org) extension.

## Empower Your Flarum Moderators with GeoIP

Moderators play a crucial role in maintaining the health and quality of forums. With GeoIP, give them the geolocation tools they need to better understand users, make informed decisions, and maintain a safe environment. Only moderators have access to IP-based geolocation, ensuring user privacy and data security.

### 🌎 Key Features
- **Location Insights**: Enable moderators to identify the country and region of users.
- **Interactive Mapping**: Let moderators visualize user locations with an integrated map view.
- **Threat Detection**: Equip moderators with the ability to highlight potentially malicious IP addresses through threat level indicators. (Via supported IP location data providers)

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
