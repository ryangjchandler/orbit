# Upgrade Guide for 2.x

One of the main goals of the 2.x release is simplifying the package's code to make maintenance a little bit easier and to also allow for some new features to be implemented.

Part of this process is dramatically improving the type-safety of the code. As such, the changes required for this upgrade _might_ take more time than usual.

**Before** going through the rest of the upgrade guide, Orbit 2.x **now requires PHP 8.2 or newer**, as well as **Laravel 10.x**. Support for Laravel 9.x has been dropped and I highly recommend you upgrade your applications.

### The new `Orbit` interface

All models using the `Orbital` trait must now also implement the `Orbit\Contracts\Orbit` interface. This is part of the journey to improved type-safety within the package's code.

```diff
- class Post extends Model
+ class Post extends Model implements Orbit
{
    use Orbital;
}
```

### `schema()` is no longer static

In 1.x, the `schema()` method for defining a model's database and file structure was a static method. In an effort to move away from static methods in the package's API, this is now an instance method.

```diff
- public static function schema(Blueprint $table)
+ public function schema(Blueprint $table): void
{
    // ...
}
```

The `void` return type has also been added to improve type-safety.

### Changing the model's driver

There were two ways of specifying / changing which driver a model should use for content management.

```php
class Post extends Model
{
    use Orbital;

    public static $driver = 'json';
}
```

```php
class Post extends Model
{
    use Orbital;

    protected static function getOrbitalDriver()
    {
        return 'json';
    }
}
```

This has been simplified and the only way to do this is now with a method override.

```php
use Orbit\Drivers\Json;

class Post extends Model implements Orbit
{
    use Orbital;

    public function getOrbitDriver(): string
    {
        return Json::class;
    }
}
```

Drivers no longer use string literals. You should instead return the fully-qualified class name of the driver you wish to use.

### New Custom Driver API

Custom drivers in 1.x were expected to implement the `Orbit\Contracts\Driver` interface.

This is still the case but the API has been simplified.

```php
interface Driver
{
    /**
     * Use the given file to generate an array of model attributes.
     */
    public function parse(string $fileContents): array;

    /**
     * Use the given array of attributes to generate the contents of a file.
     */
    public function compile(array $attributes): string;

    /**
     * Specify the file extension used by this driver.
     */
    public function extension(): string;
}
```

* The `parse()` method will receive a file path and should return an array of attributes for the given file.
* The `compile()` method will receive an array of attributes and should return a string that represents the attributes.
* The `extension()` method should return a string defining the file extension that the driver supports.

For an example implementation, look at the [`Markdown`](/src/Drivers/Markdown.php) driver provided by the package.

The `Orbit\Drivers\FileDriver` class no longer exists. If you have a driver that extends that class, update it to use the new `Driver` interface instead. The APIs are very similar so the changes should be minimal.

Drivers classes also no longer receive an instance of the container in their constructor. They are instead instantiated via the container so any dependencies you require will be injected.

> Custom drivers no longer need to be registered with Orbit using the `Orbit::extend()` method. Models now return the fully-qualified name of the driver class instead.

### Changing a model's content directory

It was possible to change the name of the directory that a model's content is stored in using the static `getOrbitalName()` method.

The name of this method was rather vague and has been updated in 2.x.

```diff
- public static function getOrbitalName()
+ public function getOrbitSource(): string
{
    return 'my-custom-folder-name';
}
```

## Missing information?

If you encounter anything that is missing from this upgrade guide, please open an issue on the repository with the missing information and any changes you made to resolve the error / issue.
