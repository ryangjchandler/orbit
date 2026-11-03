<?php

use Illuminate\Support\Facades\Log;
use Orbit\Drivers\FlatJson;
use Tests\Fixtures\Models\Post;

/** A post stored in `content/flat_posts.json`. */
class FlatPost extends Post
{
    protected $table = 'flat_posts';

    public function getOrbitDriver(): string
    {
        return FlatJson::class;
    }
}

it('stores every record in one file keyed by its key', function () {
    $first = FlatPost::create(['title' => 'first', 'tags' => ['php']]);
    $second = FlatPost::create(['title' => 'second']);
    $third = FlatPost::create(['title' => 'third']);

    $first->update(['title' => 'updated']);
    $second->delete();
    $third->update(['id' => 30]);

    $records = readContent('flat_posts.json', FlatJson::class);

    expect(array_keys($records))->toBe([1, 30])
        ->and($records[1])->toMatchArray(['id' => 1, 'title' => 'updated', 'tags' => ['php']])
        ->and($records[30])->toMatchArray(['id' => 30, 'title' => 'third'])
        ->and(content_path('flat_posts'))->not->toBeDirectory();
});

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
