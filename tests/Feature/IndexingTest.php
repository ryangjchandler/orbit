<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Fixtures\Models\Page;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\SiteSetting;
use Tests\Fixtures\Models\Tag;

/**
 * Insert a row straight into the cache. It only survives until the cache is
 * rebuilt, so it shows whether a rebuild happened.
 */
function insertSentinel(): void
{
    DB::connection('orbit')->table('posts')->insert(['id' => 999, 'title' => 'Sentinel']);
}

/** The same model class before and after its schema gains a column. */
class SchemaChangingPost extends Post
{
    public static bool $withSubtitle = false;

    protected $table = 'posts';

    public function getOrbitSource(): string
    {
        return 'posts';
    }

    public function schema(Blueprint $table): void
    {
        parent::schema($table);

        if (static::$withSubtitle) {
            $table->string('subtitle')->nullable();
        }
    }
}

function sentinelSurvived(): bool
{
    return Post::whereKey(999)->exists();
}

it('indexes files that were written by hand', function () {
    writeContent('posts/1.md', "---\nid: 1\ntitle: 'First'\n---\nThe body");
    writeContent('posts/2.md', "---\nid: 2\ntitle: 'Second'\n---\n");

    expect(Post::orderBy('id')->pluck('id')->all())->toBe([1, 2])
        ->and(Post::find(1))
        ->getRawOriginal('title')->toBe('First')
        ->content->toBe('The body');
});

it('creates the cache database when it does not exist', function () {
    DB::purge('orbit');
    unlink(config('orbit.paths.database'));
    nextRequest();

    writeContent('posts/1.md', "---\nid: 1\ntitle: 'First'\n---\n");

    expect(Post::count())->toBe(1)
        ->and(config('orbit.paths.database'))->toBeFile();
});

it('only indexes files with the driver\'s extension', function () {
    writeContent('posts/1.md', "---\nid: 1\ntitle: 'Markdown'\n---\n");
    writeContent('posts/2.json', '{"id": 2, "title": "JSON"}');
    writeContent('posts/notes.txt', 'Not content');
    mkdir(content_path('posts/3.md'));

    expect(Post::pluck('id')->all())->toBe([1]);
});

it('uses the file name as an integer key when the file has no key', function () {
    writeContent('posts/42.md', "---\ntitle: 'No id'\n---\n");

    expect(Post::find(42)?->getRawOriginal('title'))->toBe('No id');
});

it('uses the file name as a string key when the file has no key', function () {
    writeContent('pages/hello-world.md', "---\ntitle: Hello\n---\nBody");
    writeContent('site_settings/theme.json', '{"value": {"mode": "dark"}}');

    expect(Page::find('hello-world'))->title->toBe('Hello')->content->toBe('Body')
        ->and(SiteSetting::find('theme')->value)->toBe(['mode' => 'dark']);
});

it('prefers the key in the file over the file name', function () {
    writeContent('posts/1.md', "---\nid: 7\ntitle: 'Mismatch'\n---\n");

    expect(Post::pluck('id')->all())->toBe([7]);
});

it('converts casted values written by hand', function () {
    // YAML parses an unquoted date as a Unix timestamp.
    writeContent('posts/1.md', <<<'MD'
    ---
    id: 1
    title: 'By hand'
    tags: [php, laravel]
    published_at: 2026-08-31 10:00:00
    ---
    MD);

    $post = Post::find(1);

    expect($post->tags)->toBe(['php', 'laravel'])
        ->and($post->getRawOriginal('published_at'))->toBe('2026-08-31 10:00:00')
        ->and(Post::whereDate('published_at', '2026-08-31')->exists())->toBeTrue()
        ->and(Post::whereJsonContains('tags', 'laravel')->exists())->toBeTrue();
});

it('indexes into the table named by the model', function () {
    writeContent('tags/1.md', "---\nid: 1\nname: php\n---\n");

    expect(Tag::pluck('name')->all())->toBe(['php'])
        ->and(DB::connection('orbit')->getSchemaBuilder()->hasTable('post_tags'))->toBeTrue();
});

it('adds timestamp columns only for models that use timestamps', function () {
    Post::count();
    SiteSetting::count();

    $schema = DB::connection('orbit')->getSchemaBuilder();

    expect($schema->hasColumns('posts', ['created_at', 'updated_at']))->toBeTrue()
        ->and($schema->hasColumn('site_settings', 'created_at'))->toBeFalse();
});

it('picks up files added, edited and deleted by hand on the next request', function () {
    $edited = Post::create(['title' => 'Original']);
    $deleted = Post::create(['title' => 'Deleted']);

    writeContent("posts/{$edited->id}.md", "---\nid: {$edited->id}\ntitle: 'Edited by hand'\n---\n");
    unlink(content_path("posts/{$deleted->id}.md"));
    writeContent('posts/100.md', "---\nid: 100\ntitle: 'Added by hand'\n---\n");

    nextRequest();

    // toBase() skips the title accessor.
    expect(Post::orderBy('id')->toBase()->pluck('title', 'id')->all())
        ->toBe([$edited->id => 'Edited by hand', 100 => 'Added by hand']);
});

it('checks files for changes once per request', function () {
    Post::count();

    writeContent('posts/1.md', "---\nid: 1\ntitle: 'Later'\n---\n");

    expect(Post::count())->toBe(0);

    nextRequest();

    expect(Post::count())->toBe(1);
});

it('picks up an edit in the same second that keeps the file size', function () {
    $post = Post::create(['title' => 'aaaa']);
    $path = content_path("posts/{$post->id}.md");
    $mtime = filemtime($path);

    file_put_contents($path, str_replace('aaaa', 'bbbb', file_get_contents($path)));
    touch($path, $mtime);
    nextRequest();

    expect(Post::find($post->id)->getRawOriginal('title'))->toBe('bbbb');
});

it('does not rebuild after its own writes', function () {
    $post = Post::create(['title' => 'Written']);
    insertSentinel();

    $post->update(['title' => 'Updated']);
    Post::create(['title' => 'Another']);
    $post->delete();
    nextRequest();

    expect(sentinelSurvived())->toBeTrue();
});

it('does not rebuild once recently written files are no longer recent', function () {
    Post::create(['title' => 'Written']);
    insertSentinel();

    // Recent files are compared by content hash until they are older than a second.
    sleep(2);
    nextRequest();
    Post::count();
    nextRequest();

    expect(sentinelSurvived())->toBeTrue();
});

it('rebuilds a model without touching other models', function () {
    Post::create(['title' => 'Post']);
    insertSentinel();

    writeContent('pages/about.md', "---\ntitle: About\n---\n");
    nextRequest();

    expect(Page::count())->toBe(1)
        ->and(sentinelSurvived())->toBeTrue();
});

it('rebuilds the table when the schema changes', function () {
    Log::spy();
    SchemaChangingPost::$withSubtitle = false;

    // The file only fits the new schema, so it's skipped until the schema changes.
    writeContent('posts/1.md', "---\nid: 1\ntitle: Schema\nsubtitle: 'New column'\n---\n");

    expect(SchemaChangingPost::count())->toBe(0);

    SchemaChangingPost::$withSubtitle = true;
    nextRequest();

    expect(SchemaChangingPost::find(1)?->subtitle)->toBe('New column');
});

it('skips files that cannot be indexed and warns once until they change', function () {
    Log::spy();

    $post = Post::create(['title' => 'Good']);
    $broken = writeContent('posts/7.md', "---\nid: 7\ntitle: [unclosed\n---\n");
    nextRequest();

    expect(Post::pluck('id')->all())->toBe([$post->id]);

    nextRequest();
    Post::count();

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, $broken));

    writeContent('posts/7.md', "---\nid: 7\ntitle: 'Fixed'\n---\n");
    nextRequest();

    expect(Post::orderBy('id')->pluck('id')->all())->toBe([$post->id, 7]);
});

it('skips files that do not match the schema', function (string $file, string $contents) {
    Log::spy();

    writeContent('posts/1.md', "---\nid: 1\ntitle: 'Good'\n---\n");
    writeContent($file, $contents);

    expect(Post::pluck('id')->all())->toBe([1]);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, $file));
})->with([
    'unknown column' => ['posts/2.md', "---\nid: 2\ntitle: 'Extra'\nunknown: 'column'\n---\n"],
    'missing required column' => ['posts/2.md', "---\nid: 2\n---\n"],
    'duplicate key' => ['posts/2.md', "---\nid: 1\ntitle: 'Duplicate'\n---\n"],
]);

it('skips files that are not valid for the driver', function () {
    Log::spy();

    writeContent('site_settings/good.json', '{"value": 1}');
    writeContent('site_settings/bad.json', '{"value": ');

    expect(SiteSetting::pluck('key')->all())->toBe(['good']);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, 'bad.json'));
});

it('skips files whose names cannot be integer keys', function () {
    Log::spy();

    writeContent('posts/hello-world.md', "---\ntitle: Hello\n---\n");

    expect(Post::count())->toBe(0);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, 'integer key'));
});
