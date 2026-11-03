<?php

use Orbit\Drivers\Json;

it('uses the json extension', function () {
    expect((new Json)->extension())->toBe('json');
});

it('parses a JSON object into attributes', function () {
    $json = <<<'JSON'
    {
        "name": "Ryan",
        "roles": ["admin", "editor"],
        "settings": {"theme": "dark"},
        "active": true,
        "age": 30
    }
    JSON;

    expect((new Json)->parse($json))->toBe([
        'name' => 'Ryan',
        'roles' => ['admin', 'editor'],
        'settings' => ['theme' => 'dark'],
        'active' => true,
        'age' => 30,
    ]);
});

it('compiles attributes to pretty printed JSON', function () {
    expect((new Json)->compile(['name' => 'Ryan', 'age' => 30]))
        ->toBe("{\n    \"name\": \"Ryan\",\n    \"age\": 30\n}");
});

it('round trips attributes', function () {
    $attributes = ['key' => 'site', 'value' => ['name' => 'Orbit', 'links' => ['a', 'b']], 'enabled' => false, 'ratio' => 1.5];

    $driver = new Json;

    expect($driver->parse($driver->compile($attributes)))->toBe($attributes);
});
