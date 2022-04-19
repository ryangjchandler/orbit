<?php

use Orbit\Drivers\Markdown;

return [

    'driver' => Markdown::class,

    'paths' => [
        'content' => base_path('content'),
        'cache' => storage_path('framework/cache'),
    ],

];
