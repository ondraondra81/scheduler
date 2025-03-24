<?php

declare(strict_types=1);

return [
    'task_directory' => base_path('tasks'),
    'cache_directory' => storage_path('framework/cache/scheduler'),
    'crunz' =>[
        'task_directory' => storage_path('crunz'),
        'type' => 'artisan', // artisan|file
    ],
];
