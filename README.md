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

To begin using Orbit, create a Laravel model and use the `Orbit\Concerns\Orbital` trait.

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

> 🚨 When using the shorter format, i.e. `'slug' => 'required|alpha_dasg|unique:post',`, Laravel will try to load up a real database connection which may not exist and cause your app to crash.

## Drivers

Orbit is a driver-based package, making it very easy to change the storage format of your data.

Out of the box, Orbit provides the following drivers:

* `md` -> `Orbit\Drivers\Markdown`
* `json` => `Orbit\Drivers\Json`
* `yaml` => `Orbit\Drivers\Yaml`

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

## Git Integration (experimental)

Orbit comes with convenient Git integration out of the box. This means that any changes made to your content can be automatically persisted back to your Git repository, keeping everything up-to-date.

To enable Git integration, define a new `ORBIT_GIT_ENABLED` environment variable in your `.env` file and set the value to `true`.

### Events

When Git integration is enabled, Orbit will add event listeners to the `OrbitalCreated`, `OrbitalUpdated` and `OrbitalDeleted` events and commit any changed files back to your repository.

This is extremely powerful and can greatly improve your local - production workflows.

### Customising the repository root

By default, Orbit uses the `base_path` as your repositories root directory. If this is not applicable to your application, you can change the path by defining an `ORBIT_GIT_ROOT` environment variable and setting it's value to the root of your Git repository.

### Customising the author name and email

By default, Orbit will use the system's name and email address when making commits to your repository. If you wish to change the name, use the `ORBIT_GIT_NAME` and `ORBIT_GIT_EMAIL` environment variables.

If you would like to use a more dynamic name and email address, you can use the `Orbit::resolveGitNameUsing` and `Orbit::resolveGitEmailUsing` methods instead:

```php
public function boot()
{
    Orbit::resolveGitNameUsing(function () {
        return Auth::user()->name;
    });

    Orbit::resolveGitEmailUsing(function () {
        return Auth::user()->email;
    });
}
```

### Customising the Git binary

If your Git binary is not found in the usual place (`/usr/bin/git` on most UNIX machines), you can customise the location using the `ORBIT_GIT_BINARY` environment variable.
