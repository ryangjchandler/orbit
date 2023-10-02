<?php

use Tests\Fixtures\Models\Post;

it('updates an existing file when a model is updated', function () {
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

    $post->update([
        'title' => 'Updated Example Post',
    ]);

    expect(base_path("content/posts/{$post->id}.md"))
        ->toBeFile()
        ->and(file_get_contents(base_path("content/posts/{$post->id}.md")))
        ->toContain(<<<MD
        id: $post->id
        title: 'Updated Example Post'
        MD);
});
