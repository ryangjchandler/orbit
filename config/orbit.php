<?php

return [

    'default' => env('ORBIT_DEFAULT_DRIVER', 'md'),

    'drivers' => [
        'md' => \Orbit\Drivers\Markdown::class,
    ],

    'paths' => [
        'content' => base_path('content'),
        'cache' => storage_path('framework/cache/orbit'),
    ],

];
