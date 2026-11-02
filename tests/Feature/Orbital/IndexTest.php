<?php

use Illuminate\Support\Carbon;
use Orbit\Drivers\Markdown;
use Tests\Fixtures\Models\Post;

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
