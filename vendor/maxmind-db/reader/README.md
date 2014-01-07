# MaxMind DB Reader PHP API #

## Beta Note ##

This is a beta release. The API may change before the first production
release.

## Description ##

This is the pure PHP API for reading MaxMind DB files. MaxMind DB is a binary
file format that stores data indexed by IP address subnets (IPv4 or IPv6).

## Installation ##

### Define Your Dependencies ###

We recommend installing this package with [Composer](http://getcomposer.org/).
To do this, add ```maxmind-db/reader``` to your ```composer.json``` file.

```json
{
    "require": {
        "maxmind-db/reader": "0.2.*"
    }
}
```

### Install Composer ###

Run in your project root:

```
curl -s http://getcomposer.org/installer | php
```

### Install Dependencies ###

Run in your project root:

```
php composer.phar install
```

### Require Autoloader ###

You can autoload all dependencies by adding this to your code:
```
require 'vendor/autoload.php';
```

## Usage ##

## Example ##

```php
<?php
require_once 'vendor/autoload.php';

use MaxMind\Db\Reader;

$ipAddress = '24.24.24.24';
$databaseFile = 'GeoIP2-City.mmdb';

$reader = new Reader($databaseFile);

print_r($reader->get($ipAddress));
//...
```

## Optional PHP C Extension ##

MaxMind provides an optional C extension that is a drop-in replacement for for
`MaxMind\Db\Reader`. In order to use this extension, you must install the
Reader API as described above and install the extension as described below. If
you are using an autoloader, no changes to your code should be necessary.

### Installing Extension ###

```
cd ext
phpize
./configure
make
make test
make install
```

You then must load your extension. The recommend method is to add the
following to your `php.ini` file:

```
extension=maxminddb.so
```

## Support ##

Please report all issues with this code using the [GitHub issue tracker]
(https://github.com/maxmind/MaxMind-DB-Reader-php/issues).

If you are having an issue with a MaxMind service that is not specific to the
client API, please see [our support page](http://www.maxmind.com/en/support).

## Requirements  ##

This library requires PHP 5.3 or greater. Older versions of PHP are not
supported.

## Contributing ##

Patches and pull requests are encouraged. All code should follow the PSR-1 and
PSR-2 style guidelines. Please include unit tests whenever possible.

## Versioning ##

The MaxMind DB Reader PHP API uses [Semantic Versioning](http://semver.org/).

## Copyright and License ##

This software is Copyright (c) 2013 by MaxMind, Inc.

This is free software, licensed under the GNU Lesser General Public License
version 2.1 or later.
