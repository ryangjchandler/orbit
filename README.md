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

Just like a database migration, you need to define the different pieces of data that your Orbit model can have. Add a `Model::schema` method to your model.

This method will need to accept an instance of `Illuminate\Database\Schema\Blueprint`, just like a migration.

```php
use Illuminate\Database\Schema\Blueprint;

class Post extends Model
{
    use Orbit\Concerns\Orbital;

    public function schema(Blueprint $table)
    {
        $table->string('title');
        $table->string('slug');
        $table->text('content')->nullable();
        $table->timestamp('published_at');
    }
}
```

> If some of your data is optional, make sure you make the corresponding column `nullable` as well.
