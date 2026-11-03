<?php

use Tests\Fixtures\Models\Author;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\Tag;

beforeEach(function () {
    writeContent('people/1.yml', "id: 1\nname: Ryan\n");
    writeContent('people/2.yml', "id: 2\nname: Taylor\n");

    foreach (range(1, 5) as $id) {
        $author = $id <= 3 ? 1 : 2;
        writeContent("posts/{$id}.md", "---\nid: {$id}\ntitle: 'post {$id}'\nviews: ".($id * 10)."\nauthor_id: {$author}\n---\n");
    }
});

it('answers Eloquent queries from the files', function () {
    expect(Post::where('views', '>', 20)->orderByDesc('views')->pluck('id')->all())->toBe([5, 4, 3])
        ->and(Post::sum('views'))->toBe(150)
        ->and(Post::where('title', 'like', '%2')->sole()->id)->toBe(2);
});

it('paginates', function () {
    $page = Post::orderBy('id')->paginate(2, page: 2);

    expect($page->total())->toBe(5)
        ->and($page->lastPage())->toBe(3)
        ->and($page->pluck('id')->all())->toBe([3, 4]);
});

it('resolves relationships between Orbit models', function () {
    expect(Post::find(4)->author->name)->toBe('Taylor')
        ->and(Author::find(1)->posts->pluck('id')->all())->toBe([1, 2, 3]);
});

it('eager loads relationships between Orbit models', function () {
    $authors = Author::with('posts')->orderBy('id')->get();

    expect($authors->map(fn (Author $author) => $author->posts->count())->all())->toBe([3, 2]);
});

// The relationship joins the pivot table without using the pivot model, so the pivot
// table must exist even when nothing else has used the pivot model yet.
it('resolves many-to-many relationships through Orbit pivot models', function () {
    writeContent('tags/1.md', "---\nid: 1\nname: php\n---\n");
    writeContent('tags/2.md', "---\nid: 2\nname: laravel\n---\n");
    writeContent('post_tags/1.md', "---\nid: 1\npost_id: 2\ntag_id: 1\n---\n");
    writeContent('post_tags/2.md', "---\nid: 2\npost_id: 4\ntag_id: 1\n---\n");

    expect(Tag::find(1)->posts->pluck('id')->all())->toBe([2, 4])
        ->and(Tag::find(2)->posts)->toBeEmpty();
});

it('queries relationship existence between Orbit models', function () {
    Post::whereKey([4, 5])->get()->each->delete();

    expect(Author::has('posts')->pluck('name')->all())->toBe(['Ryan'])
        ->and(Author::withCount('posts')->orderBy('id')->pluck('posts_count')->all())->toBe([3, 0]);
});

// A documented limitation: query-level writes don't load models, so they don't write
// files, and the next rebuild restores what the files say.
it('does not write files for query-level writes', function () {
    Post::where('views', '>', 30)->delete();
    Post::query()->update(['title' => 'bulk']);

    expect(Post::count())->toBe(3)
        ->and(content_path('posts/5.md'))->toBeFile()
        ->and(readContent('posts/1.md')['title'])->toBe('post 1');

    $this->artisan('orbit:clear');

    expect(Post::count())->toBe(5)
        ->and(Post::find(1)->getRawOriginal('title'))->toBe('post 1');
});
