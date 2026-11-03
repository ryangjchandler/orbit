<?php

use Orbit\Drivers\Yaml;

it('uses the yml extension', function () {
    expect((new Yaml)->extension())->toBe('yml');
});

it('parses a YAML document into attributes', function () {
    $yaml = <<<'YML'
    name: Ryan
    email: "ryan@test.com"
    roles:
      - admin
      - editor
    YML;

    expect((new Yaml)->parse($yaml))->toBe([
        'name' => 'Ryan',
        'email' => 'ryan@test.com',
        'roles' => ['admin', 'editor'],
    ]);
});

it('round trips attributes, including deeply nested ones', function () {
    $attributes = [
        'name' => "O'Brien: the second",
        'roles' => ['admin', 'editor'],
        'settings' => ['notifications' => ['email' => ['daily' => true]]],
        'age' => 30,
    ];

    $driver = new Yaml;

    expect($driver->parse($driver->compile($attributes)))->toBe($attributes);
});
