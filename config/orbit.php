<?php

return [

    'paths' => [
        'database' => storage_path('framework/cache/orbit/database.sqlite'),
        'content' => base_path('content'),
    ],

    // Directories that `orbit:cache` scans for Orbit models.
    'models' => [
        app_path('Models'),
    ],

];
