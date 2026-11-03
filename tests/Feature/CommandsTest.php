<?php

use Illuminate\Support\Facades\DB;
use Tests\Fixtures\Models\Category;
use Tests\Fixtures\Models\Page;
use Tests\Fixtures\Models\Post;

function orbitTables(): array
{
    return array_column(DB::connection('orbit')->getSchemaBuilder()->getTables(), 'name');
}

beforeEach(function () {
    config(['orbit.models' => [dirname(__DIR__).'/Fixtures/Models']]);
});

it('builds the cache for every Orbit model in the configured directories', function () {
    writeContent('posts/1.md', "---\nid: 1\ntitle: 'Cached'\n---\n");

    $this->artisan('orbit:cache')
        ->expectsOutputToContain('Orbit cache built.')
        ->assertSuccessful();

    // Abstract and non-Orbit models in the directory are skipped.
    expect(orbitTables())->toEqualCanonicalizing(['_orbit_meta', 'authors', 'categories', 'pages', 'posts', 'post_tags', 'site_settings'])
        ->and(DB::connection('orbit')->table('posts')->pluck('id')->all())->toBe([1]);
});

it('stops checking files for changes once cached', function () {
    $post = Post::create(['title' => 'Cached']);

    $this->artisan('orbit:cache')->assertSuccessful();

    unlink(content_path("posts/{$post->id}.md"));
    writeContent('posts/2.md', "---\nid: 2\ntitle: 'Added'\n---\n");
    nextRequest();

    expect(Post::pluck('id')->all())->toBe([$post->id]);
});

it('still writes files while cached', function () {
    $this->artisan('orbit:cache')->assertSuccessful();
    nextRequest();

    $post = Post::create(['title' => 'Written']);
    $post->update(['title' => 'Updated']);

    expect(readContent("posts/{$post->id}.md")['title'])->toBe('Updated');
});

it('indexes models outside the configured directories when first used while cached', function () {
    config(['orbit.models' => []]);
    writeContent('posts/1.md', "---\nid: 1\ntitle: 'Indexed later'\n---\n");

    $this->artisan('orbit:cache')->assertSuccessful();

    expect(orbitTables())->toBe(['_orbit_meta']);

    nextRequest();

    expect(Post::pluck('id')->all())->toBe([1]);
});

it('skips configured directories that do not exist', function () {
    config(['orbit.models' => ['/does/not/exist']]);

    $this->artisan('orbit:cache')->assertSuccessful();

    expect(orbitTables())->toBe(['_orbit_meta']);
});

it('rebuilds from the files when cached again', function () {
    $this->artisan('orbit:cache')->assertSuccessful();

    writeContent('pages/new.md', "---\ntitle: 'New'\n---\n");
    $this->artisan('orbit:cache')->assertSuccessful();
    nextRequest();

    expect(Page::pluck('slug')->all())->toBe(['new']);
});

it('clears the cache so it is rebuilt from the files', function () {
    $post = Post::create(['title' => 'Cached']);
    Category::create(['title' => 'Cached']);
    $this->artisan('orbit:cache')->assertSuccessful();

    $this->artisan('orbit:clear')
        ->expectsOutputToContain('Orbit cache cleared.')
        ->assertSuccessful();

    // The tables are dropped but the database file is kept for workers that have it open.
    expect(orbitTables())->toBe([])
        ->and(config('orbit.paths.database'))->toBeFile();

    unlink(content_path("posts/{$post->id}.md"));
    writeContent('posts/2.md', "---\nid: 2\ntitle: 'Added'\n---\n");

    // Files are checked for changes again, in this request and the next.
    expect(Post::pluck('id')->all())->toBe([2]);

    writeContent('posts/3.md', "---\nid: 3\ntitle: 'Added later'\n---\n");
    nextRequest();

    expect(Post::orderBy('id')->pluck('id')->all())->toBe([2, 3]);
});

it('clears when the cache database does not exist', function () {
    DB::purge('orbit');
    unlink(config('orbit.paths.database'));

    $this->artisan('orbit:clear')->assertSuccessful();

    expect(config('orbit.paths.database'))->not->toBeFile();
});
