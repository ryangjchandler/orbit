<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Orbit\Drivers\Markdown;
use Tests\Fixtures\Models\Page;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\Tag;

// Each request gets a fresh index, which is when files are checked for changes.
function nextRequest(): void
{
    app()->forgetScopedInstances();
}

it('removes records whose files were deleted by hand', function () {
    $keep = Post::create(['title' => 'Keep']);
    $remove = Post::create(['title' => 'Remove']);

    unlink(base_path("content/posts/{$remove->id}.md"));
    nextRequest();

    expect(Post::pluck('id')->all())->toBe([$keep->id]);
});

it('picks up files edited by hand', function () {
    $post = Post::create(['title' => 'Original']);

    file_put_contents(base_path("content/posts/{$post->id}.md"), <<<MD
    ---
    id: {$post->id}
    title: 'Edited by hand'
    ---
    MD);
    nextRequest();

    expect(Post::find($post->id)->getRawOriginal('title'))->toBe('Edited by hand');
});

it('uses the file name as the key when the file does not define one', function () {
    file_put_contents(base_path('content/posts/42.md'), <<<'MD'
    ---
    title: 'No id'
    ---
    MD);

    expect(Post::find(42)?->getRawOriginal('title'))->toBe('No id');
});

it('stores casted attributes as readable values that round trip', function () {
    $post = Post::create([
        'title' => 'Casts',
        'tags' => ['php', 'laravel'],
        'published_at' => Carbon::parse('2026-01-02 03:04:05'),
    ]);

    $file = (new Markdown)->parse(file_get_contents(base_path("content/posts/{$post->id}.md")));

    expect($file['tags'])->toBe(['php', 'laravel'])
        ->and($file['published_at'])->toBe('2026-01-02T03:04:05+00:00');

    $this->artisan('orbit:clear');

    $post = Post::find($post->id);

    expect($post->tags)->toBe(['php', 'laravel'])
        ->and($post->published_at->toDateTimeString())->toBe('2026-01-02 03:04:05');
});

// Eloquent merges `casts()` and reads class attributes like `#[Table]` in the model's
// constructor, so indexing must use a constructed model. See #183.
it('applies casts declared in the casts method to files written by hand', function () {
    // YAML parses an unquoted date as a Unix timestamp.
    file_put_contents(base_path('content/posts/1.md'), <<<'MD'
    ---
    id: 1
    title: 'By hand'
    published_at: 2026-08-31 10:00:00
    ---
    MD);

    expect(Post::find(1)->getRawOriginal('published_at'))->toBe('2026-08-31 10:00:00')
        ->and(Post::whereDate('published_at', '2026-08-31')->exists())->toBeTrue();
});

it('indexes models into the table named by the table attribute', function () {
    File::ensureDirectoryExists(base_path('content/tags'));
    file_put_contents(base_path('content/tags/1.md'), <<<'MD'
    ---
    id: 1
    name: 'php'
    ---
    MD);

    expect(Tag::pluck('name')->all())->toBe(['php'])
        ->and(DB::connection('orbit')->getSchemaBuilder()->hasTable('post_tags'))->toBeTrue();
});

it('does not keep a record when its file cannot be written', function () {
    app()->bind(Markdown::class, fn () => new class extends Markdown
    {
        public function compile(array $attributes): string
        {
            throw new RuntimeException('Disk full');
        }
    });

    expect(fn () => Post::create(['title' => 'Unwritten']))->toThrow(RuntimeException::class, 'Disk full')
        ->and(Post::count())->toBe(0);
});

it('writes files for quiet writes and increments', function () {
    $post = Post::withoutEvents(fn () => Post::create(['title' => 'Quiet']));

    $post->increment('views');
    $post->incrementEach(['views' => 2]);
    $post->updateQuietly(['title' => 'Still written']);

    $this->artisan('orbit:clear');

    $fresh = Post::find($post->id);

    expect($fresh->views)->toBe(3)
        ->and($fresh->getRawOriginal('title'))->toBe('Still written');

    $fresh->deleteQuietly();

    expect(base_path("content/posts/{$post->id}.md"))->not->toBeFile();
});

it('moves the file when the key changes', function () {
    $post = Post::create(['title' => 'Moving']);
    $old = $post->id;

    $post->update(['id' => 500]);

    expect(base_path("content/posts/{$old}.md"))->not->toBeFile()
        ->and(base_path('content/posts/500.md'))->toBeFile();
});

it('skips files that cannot be indexed and logs a warning once', function () {
    Log::spy();

    $post = Post::create(['title' => 'Good']);
    file_put_contents(base_path('content/posts/7.md'), "---\nid: 7\ntitle: [unclosed\n---\n");
    nextRequest();

    expect(Post::pluck('id')->all())->toBe([$post->id]);

    nextRequest();
    Post::count();

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, '7.md'));
});

it('rebuilds the table when the schema changes', function () {
    $post = Post::create(['title' => 'Schema']);

    file_put_contents(base_path("content/posts/{$post->id}.md"), <<<MD
    ---
    id: {$post->id}
    title: Schema
    subtitle: 'New column'
    ---
    MD);
    touch(base_path("content/posts/{$post->id}.md"), filemtime(base_path("content/posts/{$post->id}.md")));

    $model = new class extends Post
    {
        protected $table = 'posts';

        public function getOrbitSource(): string
        {
            return 'posts';
        }

        public function schema(Blueprint $table): void
        {
            parent::schema($table);
            $table->string('subtitle')->nullable();
        }
    };

    nextRequest();

    expect($model->newQuery()->find($post->id)->subtitle)->toBe('New column');
});

it('picks up an edit made in the same second that keeps the file size', function () {
    $post = Post::create(['title' => 'aaaa']);
    $path = base_path("content/posts/{$post->id}.md");
    $mtime = filemtime($path);

    file_put_contents($path, str_replace('aaaa', 'bbbb', file_get_contents($path)));
    touch($path, $mtime);
    nextRequest();

    expect(Post::find($post->id)->getRawOriginal('title'))->toBe('bbbb');
});

it('rejects keys that would write outside the content directory', function () {
    expect(fn () => Page::create(['slug' => '../escape', 'title' => 'Nope']))
        ->toThrow(InvalidArgumentException::class)
        ->and(base_path('content/escape.md'))->not->toBeFile()
        ->and(Page::count())->toBe(0);
});

it('uses file names as string keys', function () {
    File::ensureDirectoryExists(base_path('content/pages'));
    file_put_contents(base_path('content/pages/hello-world.md'), "---\ntitle: Hello\n---\nBody");

    expect(Page::find('hello-world')?->title)->toBe('Hello');
});

it('skips non-numeric file names for integer keys with a clear warning', function () {
    Log::spy();

    file_put_contents(base_path('content/posts/hello-world.md'), "---\ntitle: Hello\n---\n");

    expect(Post::count())->toBe(0);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, 'integer key'));
});

it('does not write null values to files', function () {
    $post = Post::create(['title' => 'Sparse']);

    expect(file_get_contents(base_path("content/posts/{$post->id}.md")))
        ->not->toContain('content:')
        ->not->toContain('null');
});

it('stops checking files after orbit:cache until orbit:clear', function () {
    $post = Post::create(['title' => 'Cached']);

    $this->artisan('orbit:cache')->assertSuccessful();
    nextRequest();

    // Models outside app/Models are indexed on first use.
    expect(Post::count())->toBe(1);

    unlink(base_path("content/posts/{$post->id}.md"));
    nextRequest();

    expect(Post::find($post->id))->not->toBeNull();

    $this->artisan('orbit:clear')->assertSuccessful();

    expect(Post::find($post->id))->toBeNull();
});
