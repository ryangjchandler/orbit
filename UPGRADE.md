# Upgrade Guide

## Upgrade from `v1.x` to `v2.x`

The release of v2.x contains quite a few breaking changes. The package itself does the same thing but the configuration language is different.

### Model configuration

When configuring your models in Orbit 1.x, you would typically create methods such as `getOrbitalDriver`, etc. This has now been minified into a single `getOrbitOptions` method that uses a builder object for configuration.

**Before**:

```php
class MyModel extends Model
{
    use Orbital;

    public static function getOrbitalDriver()
    {
        return 'json';
    }

    public static function getOrbitalPath()
    {
        return 'my-custom-path';
    }
}
```

**After**:

```php
use Orbit\OrbitOptions;
use Orbit\Drivers\Json;

class MyModel extends Model
{
    use Orbital;

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::default()
            ->driver(Json::class)
            ->source('my-custom-path');
    }
}
```

## Upgrade from `v0.x` to `v1.x`

The release of v1.x of Orbit contains a couple of breaking changes and incompatibilities with previous versions. Please see the information below:

### Git integration has been removed

The first-party Git integration has been removed. This functionality will return at some point in the future as an additional package for Orbit. If you were using the integration before, you will be able to implement it yourself in the meantime by looking at the source for v0.9.x of Orbit.

### `md_json` driver has been removed

The `md_json` (`MarkdownJson`) driver wasn't incredibly popular and in order to streamline this package, I've decided to remove it from Orbit. If you wish to continue using this driver in your application, you can look at [this file](https://github.com/ryangjchandler/orbit/blob/v0.9.1/src/Drivers/MarkdownJson.php) and create your own custom driver.
