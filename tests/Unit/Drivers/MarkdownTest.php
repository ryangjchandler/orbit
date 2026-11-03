<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Drivers\Markdown;

function markdownBlueprint(): Blueprint
{
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'));
    $connection->useDefaultSchemaGrammar();

    return new Blueprint($connection, 'posts');
}

it('uses the md extension', function () {
    expect((new Markdown)->extension())->toBe('md');
});

it('parses front matter into attributes and the body into content', function () {
    $markdown = <<<'MD'
    ---
    title: Hello
    tags: [1, 'foo']
    published: true
    ---

    # Heading

    Paragraph.

    MD;

    expect((new Markdown)->parse($markdown))->toBe([
        'title' => 'Hello',
        'tags' => [1, 'foo'],
        'published' => true,
        'content' => "# Heading\n\nParagraph.",
    ]);
});

it('parses unquoted YAML dates as Unix timestamps', function () {
    $markdown = "---\npublished_at: 2023-09-30 00:00:00\n---\n";

    expect((new Markdown)->parse($markdown)['published_at'])->toBe(1696032000);
});

it('treats a file without front matter as content only', function () {
    expect((new Markdown)->parse("Just a body\n"))->toBe(['content' => 'Just a body']);
});

it('gives an empty body an empty content string', function () {
    expect((new Markdown)->parse("---\ntitle: Hello\n---\n"))->toBe(['title' => 'Hello', 'content' => '']);
});

it('compiles content into the body instead of the front matter', function () {
    $compiled = (new Markdown)->compile(['id' => 1, 'title' => 'Hello', 'content' => '# Body']);

    expect($compiled)
        ->toStartWith("---\n")
        ->toEndWith("---\n# Body")
        ->toContain("title: Hello\n")
        ->not->toContain('content:');
});

it('compiles attributes without content to an empty body', function () {
    expect((new Markdown)->compile(['id' => 1]))->toEndWith("---\n");
});

it('round trips attributes, including bodies containing a front matter delimiter', function () {
    $attributes = [
        'id' => 1,
        'title' => "It's: a 'quoted' title",
        'tags' => ['php', 'laravel'],
        'meta' => ['seo' => ['title' => 'Nested']],
        'draft' => false,
        'content' => "Intro\n\n---\n\nAfter a horizontal rule.",
    ];

    $driver = new Markdown;

    expect($driver->parse($driver->compile($attributes)))->toBe($attributes);
});

it('adds a nullable content column when the schema does not define one', function () {
    $blueprint = markdownBlueprint();
    $blueprint->string('title');

    expect(new Markdown)->toBeInstanceOf(ModifiesSchema::class);

    (new Markdown)->schema($blueprint);

    $content = collect($blueprint->getColumns())->firstWhere('name', 'content');

    expect($content)->not->toBeNull()
        ->and($content->type)->toBe('text')
        ->and($content->nullable)->toBeTrue();
});

it('keeps a content column the schema already defines', function () {
    $blueprint = markdownBlueprint();
    $blueprint->longText('content');

    (new Markdown)->schema($blueprint);

    $columns = collect($blueprint->getColumns())->where('name', 'content');

    expect($columns)->toHaveCount(1)
        ->and($columns->first()->type)->toBe('longText');
});
