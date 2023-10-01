<?php

use Orbit\Drivers\Markdown;
use PHPUnit\Framework\TestCase;

uses(TestCase::class);

it('can parse a markdown file into an array of attributes', function () {
    $markdown = <<<'MD'
    ---
    name: Ryan
    categories: [1, 'foo']
    published_at: 2023-09-30 00:00:00
    is_published: true
    ---

    # Here is the content of the file!
    MD;

    $driver = new Markdown();

    expect($driver->parse($markdown))
        ->toBeArray()
        ->toBe([
            'name' => 'Ryan',
            'categories' => [1, 'foo'],
            'published_at' => 1696032000,
            'is_published' => true,
            'content' => '# Here is the content of the file!',
        ]);
});
