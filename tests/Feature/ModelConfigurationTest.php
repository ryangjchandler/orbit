<?php

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Driver;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Drivers\Json;
use Orbit\Drivers\Markdown;
use Orbit\Drivers\Yaml;
use Orbit\Exceptions\InvalidDriverException;
use Tests\Fixtures\Models\Author;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\SiteSetting;
use Tests\Fixtures\Models\Tag;

/** A model that stores its records with the given driver in `content/custom`. */
function modelUsingDriver(string $driver): Post
{
    $model = new class extends Post
    {
        public static string $driver;

        protected $table = 'custom';

        public function getOrbitDriver(): string
        {
            return self::$driver;
        }

        public function getOrbitSource(): string
        {
            return 'custom';
        }
    };

    $model::$driver = $driver;

    return $model;
}

class KeyValueDriver implements Driver, ModifiesSchema
{
    public function __construct(public Repository $config) {}

    public function parse(string $fileContents): array
    {
        return collect(explode("\n", trim($fileContents)))
            ->mapWithKeys(fn (string $line) => [strstr($line, '=', true) => substr(strstr($line, '='), 1)])
            ->all();
    }

    public function compile(array $attributes): string
    {
        return collect($attributes)->map(fn ($value, $key) => "{$key}={$value}")->implode("\n");
    }

    public function extension(): string
    {
        return 'txt';
    }

    public function schema(Blueprint $table): void
    {
        $table->string('format')->default('key-value');
    }
}

it('always uses the orbit connection', function () {
    $post = new Post;

    expect($post->getConnectionName())->toBe('orbit')
        ->and($post->getConnection()->getName())->toBe('orbit')
        ->and(Post::query()->getConnection()->getName())->toBe('orbit');
});

it('uses the Markdown driver by default', function () {
    expect((new Post)->getOrbitDriver())->toBe(Markdown::class);
});

it('stores files in a directory named after the plural snake case class name', function () {
    expect((new Post)->getOrbitSource())->toBe('posts')
        ->and((new SiteSetting)->getOrbitSource())->toBe('site_settings')
        // The directory follows the class name, not the table name.
        ->and((new Tag)->getOrbitSource())->toBe('tags');
});

it('writes files with the model\'s driver and extension', function (string $model, array $attributes, string $file, string $driver) {
    $model::create($attributes);

    expect(readContent($file, $driver))->toMatchArray($attributes);
})->with([
    'markdown' => [Post::class, ['id' => 1, 'title' => 'hello', 'content' => 'Body'], 'posts/1.md', Markdown::class],
    'yaml' => [Author::class, ['id' => 1, 'name' => 'Ryan'], 'people/1.yml', Yaml::class],
    'json' => [SiteSetting::class, ['key' => 'theme', 'value' => ['mode' => 'dark']], 'site_settings/theme.json', Json::class],
]);

it('reads files with the model\'s driver from its source directory', function () {
    writeContent('people/1.yml', "name: Ryan\n");
    writeContent('site_settings/theme.json', '{"value": {"mode": "dark"}}');

    expect(Author::find(1)->name)->toBe('Ryan')
        ->and(SiteSetting::find('theme')->value)->toBe(['mode' => 'dark']);
});

it('supports custom drivers resolved from the container', function () {
    $model = modelUsingDriver(KeyValueDriver::class);

    writeContent('custom/1.txt', "id=1\ntitle=From a custom driver");
    writeContent('custom/ignored.md', "---\nid: 2\ntitle: Wrong extension\n---\n");

    $record = $model->newQuery()->find(1);

    // The driver's schema changes are applied to the model's table.
    expect($model->newQuery()->count())->toBe(1)
        ->and($record->getRawOriginal('title'))->toBe('From a custom driver')
        ->and($record->format)->toBe('key-value');

    $record->update(['title' => 'Updated']);

    expect(file_get_contents(content_path('custom/1.txt')))->toContain("title=Updated\n");
});

it('rejects drivers that do not exist', function () {
    expect(fn () => modelUsingDriver('Missing\\Driver')->newQuery()->count())
        ->toThrow(InvalidDriverException::class, 'Driver Missing\\Driver is invalid');
});

it('rejects drivers that do not implement the driver contract', function () {
    expect(fn () => modelUsingDriver(stdClass::class)->newQuery()->count())
        ->toThrow(InvalidDriverException::class, 'Driver stdClass is invalid');
});
