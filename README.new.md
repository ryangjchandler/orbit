# Orbit

This package allows you to interact with flat-files as if they are normal Eloquent models stored inside of an SQL database.

It does this by creating an on-disk SQLite database that transforms your flat-files into valid SQL datasets.

## Installation

You can install this package with Composer:

```bash
composer require ryangjchandler/orbit
```

## Usage

Begin by using the `Orbit\Concerns\Orbital` trait on your Eloquent model class:

```php
use Orbit\Concerns\Orbital;
use Orbit\Contracts\IsOrbital;

class Post extends Model
{
    use Orbital;
}
```

This trait contains 2 abstract methods that need to be implemented by your class:

```php
use Orbit\OrbitOptions;
use Illuminate\Database\Schema\Blueprint;

class Post extends Model
{
    use Orbital;

    public static function schema(Blueprint $table): void
    {
        //
    }

    public static function getOrbitOptions(): OrbitOptions
    {
        return OrbitOptions::default();
    }
}
```

## Configuration

The `getOrbitOptions()` method on your `Model` class should be used to configure Orbit for that particular model.

You can use the `OrbitOptions` object to configure the following things:

```php
use Orbit\Drivers\Markdown;

public static function getOrbitOptions(): OrbitOptions
{
    return OrbitOptions::make()
        // Change the driver that the model uses, default is Markdown.
        ->driver(Markdown::class)
        // Change the directory that content should be loaded from,
        // default is a kebab-case version of the model name.
        ->source('my-posts')
        // The callback provided to this function will allow you to specify
        // a custom route-like pattern, used by Orbit to generate the filename
        // for new records, as well as existing records being updated.
        // Default is simply `{getKeyName}`.
        ->generateFilenameUsing(function (): string {
            return '{created_at:Y}/{created_at:m}/{created_at:d}/{slug}';
        });
}
```
