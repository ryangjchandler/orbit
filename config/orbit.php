<?php

use Orbit\Drivers\Markdown;

return [

    'driver' => Markdown::class,

    'paths' => [
        'content' => base_path('content'),
        'cache' => storage_path('framework/cache/orbit.sqlite'),
    ],

    'connection' => 'orbit',

    'manual_mode' => env('ORBIT_MANUAL_MODE', true)

];
