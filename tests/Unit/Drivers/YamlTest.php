<?php

use Orbit\Drivers\Yaml;

it('can parse a yaml file into an array of attributes', function () {
    $yaml = <<<'YML'
    name: Ryan
    email: "ryan@test.com"
    YML;

    $driver = new Yaml();

    expect($driver->parse($yaml))
        ->toBe([
            'name' => 'Ryan',
            'email' => 'ryan@test.com',
        ]);
});
