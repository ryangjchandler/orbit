<?php

use Illuminate\Support\Facades\DB;
use Orbit\Drivers\Markdown;
use Tests\Fixtures\Models\Category;

it('adds a deleted_at column to the table', function () {
    Category::count();

    expect(DB::connection('orbit')->getSchemaBuilder()->hasColumn('categories', 'deleted_at'))->toBeTrue();
});

it('keeps the file and records deleted_at when soft deleting', function () {
    $category = Category::create(['title' => 'Example']);

    $category->delete();

    expect(readContent("categories/{$category->id}.md")['deleted_at'])->toBe($category->deleted_at->toIso8601String())
        ->and(Category::count())->toBe(0)
        ->and(Category::withTrashed()->count())->toBe(1);
});

it('removes deleted_at from the file when restoring', function () {
    $category = Category::create(['title' => 'Example']);
    $category->delete();

    $category->restore();

    expect(readContent("categories/{$category->id}.md"))->not->toHaveKey('deleted_at')
        ->and(Category::count())->toBe(1);
});

it('deletes the file when force deleting', function () {
    $category = Category::create(['title' => 'Example']);

    $category->forceDelete();

    expect(content_path("categories/{$category->id}.md"))->not->toBeFile()
        ->and(Category::withTrashed()->count())->toBe(0);
});

it('deletes the file when force deleting a soft deleted model', function () {
    $category = Category::create(['title' => 'Example']);
    $category->delete();

    Category::withTrashed()->find($category->id)->forceDelete();

    expect(content_path("categories/{$category->id}.md"))->not->toBeFile();
});

it('indexes soft deleted records from their files', function () {
    writeContent('categories/1.md', "---\nid: 1\ntitle: 'Live'\n---\n");
    writeContent('categories/2.md', "---\nid: 2\ntitle: 'Trashed'\ndeleted_at: '2026-01-01T00:00:00+00:00'\n---\n");

    expect(Category::pluck('id')->all())->toBe([1])
        ->and(Category::onlyTrashed()->pluck('id')->all())->toBe([2])
        ->and(Category::onlyTrashed()->first()->deleted_at->toDateTimeString())->toBe('2026-01-01 00:00:00');
});

it('rolls back a soft delete when its file cannot be written', function () {
    $category = Category::create(['title' => 'Example']);

    app()->bind(Markdown::class, fn () => new class extends Markdown
    {
        public function compile(array $attributes): string
        {
            throw new RuntimeException('Disk full');
        }
    });
    nextRequest();

    expect(fn () => Category::find($category->id)->delete())->toThrow(RuntimeException::class, 'Disk full')
        ->and(Category::count())->toBe(1)
        ->and(readContent("categories/{$category->id}.md"))->not->toHaveKey('deleted_at');
});
