<?php

return [

    'default' => env('ORBIT_DEFAULT_DRIVER', 'md'),

    'drivers' => [
        'json' => \Orbit\Drivers\Json::class,
        'md' => \Orbit\Drivers\Markdown::class,
    ],

];
