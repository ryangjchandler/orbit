<?php

use Tests\Fixtures\Models\FlatJsonModel;
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

it('does not use accessors when serialising saved data', function () {
    $post = Post::create([
        'title' => 'hello',
    ]);

    expect(base_path("content/posts/{$post->id}.md"))
        ->toBeFile()
        ->and(file_get_contents(base_path("content/posts/{$post->id}.md")))
        ->not->toContain('Hello');
});

it('creates a single file when multiple models are created using the flat json driver', function () {
    $post1 = FlatJsonModel::create([
        'title' => 'Example Post 1',
    ]);

    $post2 = FlatJsonModel::create([
        'title' => 'Example Post 2',
    ]);

    expect(base_path("content/flat_json_models.json"))
        ->toBeFile()
        ->and(file_get_contents(base_path("content/flat_json_models.json")))
        ->json()
        ->toContain([
            'id' => $post1->id,
            'title' => 'Example Post 1',
        ])
        ->toContain([
            'id' => $post2->id,
            'title' => 'Example Post 2',
        ])
        ->toHaveKeys([$post1->id, $post2->id]);
});
