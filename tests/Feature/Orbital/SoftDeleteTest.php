<?php

use Tests\Fixtures\Models\Category;

it('does not delete the source file when soft deleting a model', function () {
    $category = Category::create([
        'title' => 'Example Category',
    ]);

    expect(base_path("content/categories/{$category->id}.md"))
        ->toBeFile();

    $category->delete();

    expect(base_path("content/categories/{$category->id}.md"))
        ->toBeFile()
        ->and(file_get_contents(base_path("content/categories/{$category->id}.md")))
        ->toContain(<<<MD
        deleted_at: {$category->deleted_at->toIso8601String()}
        MD);
});

it('updates the source file when restoring a soft deleted model', function () {
    $category = Category::create([
        'title' => 'Example Category',
    ]);

    expect(base_path("content/categories/{$category->id}.md"))
        ->toBeFile();

    $category->delete();

    expect(base_path("content/categories/{$category->id}.md"))
        ->toBeFile()
        ->and(file_get_contents(base_path("content/categories/{$category->id}.md")))
        ->toContain(<<<MD
        deleted_at: {$category->deleted_at->toIso8601String()}
        MD);

    $category->restore();

    expect(base_path("content/categories/{$category->id}.md"))
        ->toBeFile()
        ->and(file_get_contents(base_path("content/categories/{$category->id}.md")))
        ->toContain(<<<MD
        deleted_at: null
        MD);
});

it('deletes the source file when force deleting a model', function () {
    $category = Category::create([
        'title' => 'Example Category',
    ]);

    expect(base_path("content/categories/{$category->id}.md"))
        ->toBeFile();

    $category->forceDelete();

    expect(base_path("content/categories/{$category->id}.md"))
        ->not->toBeFile();
});
