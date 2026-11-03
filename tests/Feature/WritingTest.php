<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Orbit\Drivers\Markdown;
use Orbit\Drivers\Yaml;
use Tests\Fixtures\Models\Author;
use Tests\Fixtures\Models\Page;
use Tests\Fixtures\Models\Post;

/** Make every Markdown file write fail, as a full disk would. */
function failMarkdownWrites(): void
{
    app()->bind(Markdown::class, fn () => new class extends Markdown
    {
        public function compile(array $attributes): string
        {
            throw new RuntimeException('Disk full');
        }
    });

    nextRequest();
}

it('writes a file when a model is created', function () {
    Carbon::setTestNow('2026-01-02 03:04:05');

    $post = Post::create(['title' => 'hello world', 'content' => '# Hello']);

    expect(content_path("posts/{$post->id}.md"))->toBeFile()
        ->and(readContent("posts/{$post->id}.md"))->toBe([
            'id' => $post->id,
            'title' => 'hello world',
            'updated_at' => '2026-01-02T03:04:05+00:00',
            'created_at' => '2026-01-02T03:04:05+00:00',
            'content' => '# Hello',
        ]);
});

it('creates the model\'s content directory when it does not exist', function () {
    expect(content_path('pages'))->not->toBeDirectory();

    Page::count();

    expect(content_path('pages'))->toBeDirectory();
});

it('writes raw attribute values rather than accessor values', function () {
    $post = Post::create(['title' => 'lower case']);

    expect($post->title)->toBe('Lower Case')
        ->and(readContent("posts/{$post->id}.md")['title'])->toBe('lower case');
});

it('rewrites the file when a model is updated', function () {
    $post = Post::create(['title' => 'Original', 'content' => 'Old body']);

    Carbon::setTestNow(now()->addMinute());
    $post->update(['title' => 'Updated', 'content' => 'New body']);

    expect(readContent("posts/{$post->id}.md"))
        ->title->toBe('Updated')
        ->content->toBe('New body')
        ->updated_at->toBe(now()->toIso8601String());
});

it('writes the file when a model is touched', function () {
    $post = Post::create(['title' => 'Touch']);

    Carbon::setTestNow(now()->addDay());
    $post->touch();

    expect(readContent("posts/{$post->id}.md")['updated_at'])->toBe(now()->toIso8601String());
});

it('deletes the file when a model is deleted', function () {
    $keep = Post::create(['title' => 'Keep']);
    $post = Post::create(['title' => 'Delete']);

    $post->delete();

    expect(content_path("posts/{$post->id}.md"))->not->toBeFile()
        ->and(content_path("posts/{$keep->id}.md"))->toBeFile();
});

it('moves the file when the key changes', function () {
    $post = Post::create(['title' => 'Moving']);
    $old = $post->id;

    $post->update(['id' => 500]);

    expect(content_path("posts/{$old}.md"))->not->toBeFile()
        ->and(readContent('posts/500.md'))->id->toBe(500)->title->toBe('Moving');

    nextRequest();

    expect(Post::pluck('id')->all())->toBe([500]);
});

it('uses string keys as file names', function () {
    Page::create(['slug' => 'about-us', 'title' => 'About us']);

    expect(readContent('pages/about-us.md'))->toMatchArray(['slug' => 'about-us', 'title' => 'About us']);
});

it('rejects keys that are not safe file names', function (string $slug) {
    expect(fn () => Page::create(['slug' => $slug, 'title' => 'Nope']))
        ->toThrow(InvalidArgumentException::class, "can't be used as an Orbit file name")
        ->and(Page::count())->toBe(0)
        ->and(glob(content_path('*.md')))->toBe([]);
})->with([
    'parent directory' => '../escape',
    'nested directory' => 'nested/page',
    'backslash' => 'nested\\page',
    'null byte' => "page\0.php",
    'empty' => '',
]);

it('leaves null values out of files', function () {
    $post = Post::create(['title' => 'Sparse', 'content' => null, 'tags' => null]);

    expect(readContent("posts/{$post->id}.md"))->not->toHaveKeys(['tags', 'published_at'])
        ->and(file_get_contents(content_path("posts/{$post->id}.md")))->not->toContain('null');
});

it('writes array casts as lists and maps and dates as ISO 8601 strings', function () {
    $post = Post::create([
        'title' => 'Casts',
        'tags' => ['php', 'laravel'],
        'published_at' => Carbon::parse('2026-01-02 03:04:05'),
    ]);

    expect(readContent("posts/{$post->id}.md"))
        ->tags->toBe(['php', 'laravel'])
        ->published_at->toBe('2026-01-02T03:04:05+00:00');
});

it('round trips casted attributes through the files', function () {
    $post = Post::create([
        'title' => 'Casts',
        'tags' => ['php', 'laravel'],
        'published_at' => Carbon::parse('2026-01-02 03:04:05'),
    ]);

    $this->artisan('orbit:clear');

    $post = Post::find($post->id);

    expect($post->tags)->toBe(['php', 'laravel'])
        ->and($post->published_at->toDateTimeString())->toBe('2026-01-02 03:04:05');
});

it('keeps encrypted attributes encrypted in files', function () {
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    $author = Author::create(['name' => 'Ryan', 'api_token' => 'secret-token', 'recovery_codes' => ['one', 'two']]);

    $stored = readContent("people/{$author->id}.yml", Yaml::class);

    // Encrypted JSON casts must stay encrypted strings rather than be decoded into lists.
    expect($stored['api_token'])->toBeString()->not->toContain('secret-token')
        ->and(Crypt::decryptString($stored['api_token']))->toBe('secret-token')
        ->and($stored['recovery_codes'])->toBeString()->not->toContain('one')
        ->and(json_decode(Crypt::decryptString($stored['recovery_codes']), true))->toBe(['one', 'two']);

    $this->artisan('orbit:clear');

    expect(Author::find($author->id))
        ->api_token->toBe('secret-token')
        ->recovery_codes->toBe(['one', 'two']);
});

it('writes files for quiet saves, updates and deletes', function () {
    $post = Post::withoutEvents(fn () => Post::create(['title' => 'Quiet']));

    expect(readContent("posts/{$post->id}.md")['title'])->toBe('Quiet');

    $post->title = 'Saved quietly';
    $post->saveQuietly();

    expect(readContent("posts/{$post->id}.md")['title'])->toBe('Saved quietly');

    $post->updateQuietly(['title' => 'Updated quietly']);

    expect(readContent("posts/{$post->id}.md")['title'])->toBe('Updated quietly');

    $post->deleteQuietly();

    expect(content_path("posts/{$post->id}.md"))->not->toBeFile();
});

it('writes files for increments and decrements', function () {
    $post = Post::create(['title' => 'Counter', 'views' => 10]);

    $post->increment('views');
    expect(readContent("posts/{$post->id}.md")['views'])->toBe(11);

    $post->decrement('views', 3);
    expect(readContent("posts/{$post->id}.md")['views'])->toBe(8);

    $post->incrementEach(['views' => 5], ['title' => 'Extra columns too']);
    expect(readContent("posts/{$post->id}.md"))->views->toBe(13)->title->toBe('Extra columns too');

    $post->decrementEach(['views' => 13]);
    expect(readContent("posts/{$post->id}.md")['views'])->toBe(0);
});

it('does not write a file when saving is cancelled', function () {
    Post::saving(fn () => false);

    $post = new Post(['id' => 1, 'title' => 'Cancelled']);

    expect($post->save())->toBeFalse()
        ->and(content_path('posts/1.md'))->not->toBeFile();
});

it('keeps the file when deleting is cancelled', function () {
    $post = Post::create(['title' => 'Kept']);

    Post::deleting(fn () => false);

    expect($post->delete())->toBeFalse()
        ->and(content_path("posts/{$post->id}.md"))->toBeFile()
        ->and(Post::count())->toBe(1);
});

it('does not keep a created record when its file cannot be written', function () {
    failMarkdownWrites();

    expect(fn () => Post::create(['title' => 'Unwritten']))->toThrow(RuntimeException::class, 'Disk full')
        ->and(Post::count())->toBe(0)
        ->and(glob(content_path('posts/*')))->toBe([]);
});

it('rolls back an update when its file cannot be written', function () {
    $post = Post::create(['title' => 'Original']);

    failMarkdownWrites();

    expect(fn () => Post::find($post->id)->update(['title' => 'Unwritten']))->toThrow(RuntimeException::class, 'Disk full')
        ->and(Post::find($post->id)->getRawOriginal('title'))->toBe('Original')
        ->and(readContent("posts/{$post->id}.md")['title'])->toBe('Original');
});

it('rolls back an increment when its file cannot be written', function () {
    $post = Post::create(['title' => 'Counter', 'views' => 1]);

    failMarkdownWrites();

    expect(fn () => Post::find($post->id)->increment('views'))->toThrow(RuntimeException::class, 'Disk full')
        ->and(Post::find($post->id)->views)->toBe(1);
});
