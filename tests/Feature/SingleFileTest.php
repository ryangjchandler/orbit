<?php

use Illuminate\Support\Facades\Log;
use Orbit\Drivers\FlatJson;
use Orbit\Drivers\FlatYaml;
use Tests\Fixtures\Models\Post;

/** A post stored with a single-file driver in `content/flat_posts.{extension}`. */
class FlatPost extends Post
{
    public static string $driver = FlatJson::class;

    protected $table = 'flat_posts';

    public function getOrbitDriver(): string
    {
        return static::$driver;
    }
}

beforeEach(function () {
    FlatPost::$driver = FlatJson::class;
});

it('stores every record in one file keyed by its key', function (string $driver, string $file) {
    FlatPost::$driver = $driver;

    $first = FlatPost::create(['title' => 'first', 'tags' => ['php']]);
    $second = FlatPost::create(['title' => 'second']);
    $third = FlatPost::create(['title' => 'third']);

    $first->update(['title' => 'updated']);
    $second->delete();
    $third->update(['id' => 30]);

    $records = readContent($file, $driver);

    expect(array_keys($records))->toBe([1, 30])
        ->and($records[1])->toMatchArray(['id' => 1, 'title' => 'updated', 'tags' => ['php']])
        ->and($records[30])->toMatchArray(['id' => 30, 'title' => 'third'])
        ->and(content_path('flat_posts'))->not->toBeDirectory();
})->with([
    'json' => [FlatJson::class, 'flat_posts.json'],
    'yaml' => [FlatYaml::class, 'flat_posts.yml'],
]);

it('indexes records written by hand', function () {
    Log::spy();

    writeContent('flat_posts.json', json_encode([
        3 => ['title' => 'Keyed by the file'],
        'ignored' => ['id' => 7, 'title' => 'Keyed by its id'],
        4 => 'not a record',
    ]));
    writeContent('other_posts.json', json_encode([9 => ['title' => 'Another model']]));

    expect(FlatPost::orderBy('id')->toBase()->pluck('title', 'id')->all())
        ->toBe([3 => 'Keyed by the file', 7 => 'Keyed by its id']);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, 'record [4]'));

    writeContent('flat_posts.json', json_encode([3 => ['title' => 'Edited by hand']]));
    nextRequest();

    expect(FlatPost::toBase()->pluck('title', 'id')->all())->toBe([3 => 'Edited by hand']);
});

it('keeps records it could not index when writing', function () {
    writeContent('flat_posts.json', json_encode([4 => ['title' => 'Indexed'], 5 => ['views' => 'no title']]));

    FlatPost::find(4)->update(['title' => 'Updated']);

    $records = readContent('flat_posts.json', FlatJson::class);

    expect($records[4])->toMatchArray(['id' => 4, 'title' => 'Updated'])
        ->and($records[5])->toBe(['views' => 'no title']);
});

it('does not replace a file it cannot parse', function () {
    writeContent('flat_posts.json', '{"1": {"title": "Unfinished"');

    expect(rescue(fn () => FlatPost::create(['title' => 'New']), 'failed', report: false))->toBe('failed')
        ->and(file_get_contents(content_path('flat_posts.json')))->toBe('{"1": {"title": "Unfinished"')
        ->and(FlatPost::count())->toBe(0);
});
