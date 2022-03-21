![Orbit](./art/orbit.png)

<p align="center">
    <a href="https://laravel.com"><img alt="Laravel v9.x" src="https://img.shields.io/badge/Laravel-v9.x-FF2D20?style=for-the-badge&logo=laravel"></a>
    <a href="https://php.net"><img alt="PHP 8.0" src="https://img.shields.io/badge/PHP-8.0-777BB4?style=for-the-badge&logo=php"></a>
</p>

Orbit is a flat-file driver for Laravel Eloquent. It allows you to replace your generic database with real files that you can manipulate using the methods you're familiar with.

## Installation

To install Orbit, run the following command in your project:

```bash
composer require ryangjchandler/orbit
```

## Usage

To begin using Orbit, create a Laravel model and use the `Orbit\Concerns\Orbital` trait.

```php
class Post extends Model
{
    use \Orbit\Concerns\Orbital;
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
    use \Orbit\Concerns\Orbital;

    public static function schema(Blueprint $table)
    {
        $table->string('title');
        $table->string('slug');
        $table->timestamp('published_at');
    }
}
```

> If some of your data is optional, make sure the corresponding column is `nullable`.

### Storing content

By default, all content is stored inside of a `content` folder in the root of your application. If you wish to change this, publish the `orbit.php` configuration file and change the `orbit.paths.content` option.

Orbit will transform the base name of your model into a pluralized snake-cased string and use that as the main folder name, e.g. `Post` -> `content/posts`, `PostCategory` => `content/post_categories`.

> 🚨 Changing the name of a model will prevent Orbit from finding any existing records in the old folder. If you wish to change the name of the folder, overwrite the `public static function getOrbitalName` method on your model class and return the old name instead.

Any time you call `Model::create()`, `Model::update` or `Model::delete`, Orbit will intercept those calls and forward to the necessary driver method. The driver is then responsible for performing the necessary file system calls.

### Changing the primary key

Just like a normal Eloquent model, you can change the primary key of your Orbit model. Overwrite the `Model::getKeyName` method and return the name of your new model.

```php
class Post extends Model
{
    use Orbital;

    public function getKeyName()
    {
        return 'slug';
    }
    
    public function getIncrementing()
    {
        return false;
    }
}
```

> If your model's primary key (the key you defined in `getKeyName`) doesn't need to automatically increment, you should either define `public $incrementing = false` on the model or overwrite the `getIncrementing` method.

Standard Orbit drivers will respect the new key name and use that when creating, updating and deleting files on disk, e.g. a `Post` with the slug `hello-world` will write to the `content/posts/hello-world.md` file.

> 🚨 Changing the key for a model after records already exist can cause damage. Be sure to rename your files afterwards so that Orbit doesn't create duplicate content.

### Soft Deletes

Since Orbit needs to update files on disk when your model is updated, the standard Eloquent `SoftDeletes` trait doesn't quite work. If you want to add soft delete support to your Orbit model, you can instead use the `Orbit\Concerns\SoftDeletes` trait.

This trait uses the Eloquent one under-the-hood, so you can still access all of your normal `SoftDeletes` methods, including `isForceDeleting()` and `forceDelete()`. 

The Orbit version adds in the necessary hooks to perform file system operations as well as ensure you don't completely delete your content.

### Validation Rules

When dealing with [validation rules](https://laravel.com/docs/8.x/validation#available-validation-rules) that check against a database like [`exists`](https://laravel.com/docs/8.x/validation#rule-exists) and [`unique`](https://laravel.com/docs/8.x/validation#rule-unique), you should use the **fully-qualified namespace (FQN) of the model** instead of the table name.

This is because Orbit runs on a separate database connection - using the FQN will allow Laravel to correctly resolve the qualified table name.

```php
class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => 'required|alpha_dash|unique:App\Post,id',
            // 'slug' => ['required', 'alpha_dash', Rule::unique(Post::class)],
            'title' => 'required',
            'description' => 'required',
        ];
    }
  }
```

### Testing

As previously mentioned in the [Validation Rules](#validation-rules) section, Orbit operates on a custom connection called `orbit`. This means that Laravel's database testing utilities will work, as long as you specify the connection name.

```php
$this->assertDatabaseCount('posts', 5, 'orbit');

$this->assertDatabaseHas('posts', [
    'title' => 'Hello, world',
    'slug' => 'hello-world',
], 'orbit');
```

## Drivers

Orbit is a driver-based package, making it very easy to change the storage format of your data.

Out of the box, Orbit provides the following drivers:

* `md` -> `Orbit\Drivers\Markdown`
* `json` => `Orbit\Drivers\Json`
* `yaml` => `Orbit\Drivers\Yaml`

### `md`

This is a Markdown that is capable of parsing Markdown files, as well as YAML front-matter.

When Orbit loads files using this driver, it will map each front-matter key into a column in your models `schema`.

By default, the Markdown driver will also add a `TEXT content` column to your schema. This is used to store the Markdown body from the file.

> 💡 If you wish to customise the name of the `content` column, you can use the `Markdown::contentColumn()` method and provide a new column name. This is applied to all models that use the `Markdown` driver.

### `json`

This is a JSON driver that is capable of parsing `.json` files. Each key in the file is mapped to a column in your schema.

### `yaml`

This is a YAML driver that is capable of parsing `.yml` files. Each key in the file is mapped to a column in your schema.

### Registering drivers

You can register custom Orbit drivers by using the `Orbit::extend` method. You should call this method from the `boot` method in a `ServiceProvider`.

```php
\Orbit\Facades\Orbit::extend('json', function ($app) {
    return new \App\Drivers\JsonDriver($app);
})
```

All drivers must implement the `Orbit\Contracts\Driver` contract, or extend the `Orbit\Drivers\FileDriver` class. This enforces drivers to have at least 4 methods:

```php
interface Driver
{
    public function shouldRestoreCache(string $directory): bool;

    public function save(Model $model, string $directory): bool;

    public function delete(Model $model, string $directory): bool;

    public function all(string $directory): Collection;
}
```

Here is what each method is used for:

* `shouldRestoreCache` - used to determine if the file cache should be updated. 
* `save` - used to persist model changes to a file on disk, or create a new file.
* `delete` - used to delete an existing file on disk
* `all` - used to retrieve all records from disk and cache.

### Extending `FileDriver`

Extending the `Orbit\Drivers\FileDriver` class is useful when you want some of the heavy lifting done for you. To work with this base class, you should do the following:

1. Implement an `extension(): string` method that returns the file extension as a string, i.e. `return 'md'` for `Markdown`.
2. Implement a `dumpContent(Model $model): string` method. This method should return the updated content for the file.
3. Implement a `parseContent(SplFileInfo $file): array` method. This method should return an array of `key => value` pairs, where each `key` is a column in the `schema`.

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

### Disabling Orbit

If you have a model that uses the `Orbital` trait and would like to temporarily disable Orbit's functionality, you can override the `enableOrbit(): bool` method on your model:

```php
class Post extends Model
{
    use Orbital;

    public static function enableOrbit(): bool
    {
        return false;
    }
}
```

## Git Integration

Since `v1.0.0` this package no longer supports the git integration. Instead you can use [jubeki/orbit-git](https://Jubeki/orbit-git) as replacement.
