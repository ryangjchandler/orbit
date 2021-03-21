![Orbit](./art/orbit.png)

<p align="center">
    <a href="https://laravel.com"><img alt="Laravel v8.x" src="https://img.shields.io/badge/Laravel-v8.x-FF2D20?style=for-the-badge&logo=laravel"></a>
    <a href="https://php.net"><img alt="PHP 7.4" src="https://img.shields.io/badge/PHP-7.4-777BB4?style=for-the-badge&logo=php"></a>
</p>

Orbit is a flat-file driver for Laravel Eloquent. It allows you to replace your generic database with real files that you can manipulate using the methods you're familiar with.

## Installation

To install Orbit, run the following command in your project:

```bash
composer require ryangjchandler/orbit
```

## Usage

To being using Orbit, create a Laravel model and use the `Orbit\Concerns\Orbital` trait.

```php
class Post extends Model
{
    use Orbit\Concerns\Orbital;
}
```

The `Orbital` trait is responsible for hooking into the Eloquent lifecycle and ensuring all of your interactions are handled correctly.

### Defining the Schema

Just like a database migration, you need to define the different pieces of data that your Orbit model can have. Add a `public static function schema(Blueprint $table)` method to your model.

This method will need to accept an instance of `Illuminate\Database\Schema\Blueprint`, just like a migration.

```php
use Illuminate\Database\Schema\Blueprint;

class Post extends Model
{
    use Orbit\Concerns\Orbital;

    public static function schema(Blueprint $table)
    {
        $table->string('title');
        $table->string('slug');
        $table->text('content')->nullable();
        $table->timestamp('published_at');
    }
}
```

> If some of your data is optional, make sure the corresponding column is `nullable`.

## Drivers

Orbit is a driver-based package, making it very easy to change the storage format of your data.

Out of the box, Orbit provides the following drivers:

* `md` -> `Orbit\Drivers\Markdown`

### Registering drivers

You can register custom Orbit drivers by using the `Orbit::extend` method. You should call this method from the `boot` method in a `ServiceProvider`.

```php
\Orbit\Facades\Orbit::extend('json', function ($app) {
    return new \App\Drivers\JsonDriver($app);
})
```

All drivers must implement the `Orbit\Contracts\Driver` contract. This enforces drivers to have at least 4 methods:

```php
interface Driver
{
    public function shouldRestoreCache(string $directory): bool;

    public function save(Model $model, string $directory): bool;

    public function delete(Model $model, string $directory): bool;

    public function all(string $directory): Collection;
}
```

Here is what each of the methods are for:

* `shouldRestoreCache` - used to determine if the file cache should be updated. 
* `save` - used to persist model changes to a file on disk, or create a new file.
* `delete` - used to delete an existing file on disk
* `all` - used to retrieve all records from disk and cache.

### Changing drivers

If you wish to use a different driver for one of your models, you can add a `public static $driver` property to your model and set the value to the name of a driver.

```php
class Post extends Model
{
    use Orbital;

    public static $driver = 'json';
}
```

> Driver names are determined when they are registered with Orbit. You should always use the string name of the driver instead of the fully-qualified class name.
