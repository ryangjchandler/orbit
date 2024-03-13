<?php

return [

    'default' => env('ORBIT_DEFAULT_DRIVER', 'md'),

    'drivers' => [
        'md' => \Orbit\Drivers\Markdown::class,
        'json' => \Orbit\Drivers\Json::class,
        'yaml' => \Orbit\Drivers\Yaml::class,
        'csv' => \Orbit\Drivers\Csv::class,
    ],

    'paths' => [
        'content' => base_path('content'),
        'cache' => storage_path('framework/cache/orbit'),
    ],

];
