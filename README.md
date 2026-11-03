![Orbit](./art/orbit.png)

Orbit is a flat-file driver for Laravel Eloquent. Your records live in Markdown, YAML or JSON files that you can edit by hand, and you query them with the Eloquent methods you already know.

## Installation

```bash
composer require ryangjchandler/orbit
```

## Usage

Implement the `Orbit` interface, use the `Orbital` trait and describe your columns in a `schema()` method, just like a migration:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Concerns\Orbital;
use Orbit\Contracts\Orbit;

class Post extends Model implements Orbit
{
    use Orbital;

    protected $guarded = [];

    public function schema(Blueprint $table): void
    {
        $table->id();
        $table->string('title');
        $table->text('content')->nullable();
    }
}
```

Each record is stored as `content/posts/{key}.md`:

```md
---
id: 1
title: 'Hello world'
updated_at: '2026-01-01T00:00:00+00:00'
created_at: '2026-01-01T00:00:00+00:00'
---
The body of the Markdown file is stored in the `content` column.
```

Timestamps and Laravel's `SoftDeletes` trait work as usual. Soft deleted records keep their file with a `deleted_at` value.

### Hand-written files

If a file doesn't include the model's key, Orbit uses the file name. For names like `hello-world.md`, give the model a string key:

```php
protected $primaryKey = 'slug';
protected $keyType = 'string';
public $incrementing = false;

public function schema(Blueprint $table): void
{
    $table->string('slug')->primary();
    // ...
}
```

A file that can't be parsed or doesn't match the schema is skipped, and a warning is written to your log.

### Drivers

Markdown with YAML front matter is the default. To use another format, return a driver from `getOrbitDriver()`:

```php
public function getOrbitDriver(): string
{
    return \Orbit\Drivers\Yaml::class; // or \Orbit\Drivers\Json::class
}
```

A custom driver implements `Orbit\Contracts\Driver`. To store a model's files in a different directory, override `getOrbitSource()`.

## How it works

Your files are the source of truth. Orbit keeps a SQLite cache of them so queries, relationships and pagination behave exactly like a normal database.

* On the first query of each request, Orbit checks whether any file or the model's schema has changed, and rebuilds that model's cache if so.
* Saving, deleting or incrementing a model writes its file inside a transaction. If the file can't be written, the change is rolled back.

### Production

Run `php artisan orbit:cache` when you deploy. It builds the cache for every Orbit model in the directories listed in the `models` config option (`app/Models` by default) and stops checking files for changes. Run it again, or `php artisan orbit:clear`, after changing content.

Publish the config file to change these directories or the content and cache paths:

```bash
php artisan vendor:publish --tag=orbit-config
```

### Limitations

Query-level writes such as `Post::where('draft', true)->delete()`, `update()`, `insert()` or `truncate()` don't load models, so they don't update files, and their changes are lost when the cache is rebuilt. Load the models and save or delete them one by one:

```php
Post::where('draft', true)->get()->each->delete();
```
