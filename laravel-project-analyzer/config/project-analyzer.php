<?php

return [
    'analysis' => [
        'paths' => [
            'app',
            'database',
            'routes',
            'config',
            'tests',
        ],
        'exclude' => [
            'vendor',
            'storage',
            'bootstrap/cache',
            'node_modules',
        ],
        'depth' => 'full',
        'parallel' => true,
        'memory_limit' => '512M',
    ],

    'analyzers' => [
        'class' => true,
        'model' => true,
        'controller' => true,
        'route' => true,
        'database' => true,
        'service' => true,
        'test' => true,
        'security' => true,
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'driver' => 'file',
        'incremental' => true,
    ],

    'dashboard' => [
        'enabled' => true,
        'route_prefix' => 'analyzer',
        'middleware' => ['web'],
        'theme' => 'light',
    ],

    'export' => [
        'formats' => ['json', 'markdown', 'html'],
        'location' => storage_path('project-analysis'),
        'include_private' => false,
    ],

    'plugins' => [
        'enabled' => true,
        'path' => base_path('vendor/plugins'),
        'register' => [],
    ],
];
