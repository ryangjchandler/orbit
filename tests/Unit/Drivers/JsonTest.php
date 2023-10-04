<?php

use Orbit\Drivers\Json;

it('can parse a json file into an array of attributes', function () {
    $json = <<<'JSON'
    {
        "name": "Ryan",
        "email": "ryan@test.com"
    }
    JSON;

    $driver = new Json();

    expect($driver->parse($json))
        ->toBe([
            'name' => 'Ryan',
            'email' => 'ryan@test.com',
        ]);
});
