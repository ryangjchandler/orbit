<?php

use Tests\Fixtures\Models\FlatJsonModel;
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

it('deletes a entry from the file when a model is deleted using the flat json driver', function () {
    $post = FlatJsonModel::create([
        'title' => 'Example Post',
    ]);

    expect(base_path("content/flat_json_models.json"))
        ->toBeFile();

    $post->delete();

    expect(base_path("content/flat_json_models.json"))
        ->toBeFile()
        ->and(file_get_contents(base_path("content/flat_json_models.json")))
        ->not->toContain('Example Post');
});
