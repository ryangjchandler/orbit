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

    'git' => [
        'enabled' => env('ORBIT_GIT_ENABLED', false),
        'name' => env('ORBIT_GIT_NAME'),
        'email' => env('ORBIT_GIT_EMAIL'),
    ],

];
