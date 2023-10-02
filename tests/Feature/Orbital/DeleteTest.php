<?php

use Tests\Fixtures\Models\Post;

it('deletes a file when a model is deleted', function () {
    $post = Post::create([
        'title' => 'Example Post',
    ]);

    expect(base_path("content/posts/{$post->id}.md"))
        ->toBeFile();

    $post->delete();

    expect(base_path("content/posts/{$post->id}.md"))
        ->not->toBeFile();
});
