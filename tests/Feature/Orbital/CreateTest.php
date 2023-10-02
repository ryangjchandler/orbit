<?php

use Tests\Fixtures\Models\Post;

it('creates a new file when a model is created', function () {
    $post = Post::create([
        'title' => 'Example Post',
    ]);

    expect(base_path("content/posts/{$post->id}.md"))
        ->toBeFile()
        ->and(file_get_contents(base_path("content/posts/{$post->id}.md")))
        ->toContain(<<<MD
        id: $post->id
        title: 'Example Post'
        MD);
});
