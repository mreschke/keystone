<?php

return [

    // Default connection
    'default' => env('KEYSTONE_DRIVER', 'native'),


    // Keystone connections
    'connections' => [

        'native' => [
            'driver' => 'native',
            'vendor' => env('KEYSTONE_VENDOR', 'mreschke/keystone'),
            'host' => env('KEYSTONE_HOST', '127.0.0.1'),
            'password' => env('KEYSTONE_PASSWORD'),
            'port' => env('KEYSTONE_PORT', 6379),
            'database' => env('KEYSTONE_DATABASE', 0),
            'prefix' => env('KEYSTONE_PREFIX', 'keystone:'),
            'root_namespace' => env('KEYSTONE_ROOT_NAMESPACE', 'mreschke/foundation'),
            'metadata_namespace' => env('KEYSTONE_METADATA_NAMESPACE', 'mreschke/keystone'),
            'path' => env('KEYSTONE_PATH'),
            'max_redis_size' => env('KEYSTONE_MAX_REDIS_SIZE', 4096),
        ],

        'remote' => [
            'driver' => 'http',
            'vendor' => env('KEYSTONE_VENDOR', 'mreschke/keystone'),
            'api_url' => env('KEYSTONE_API_URL'),
            'api_version' => env('KEYSTONE_API_VERSION', 'v1'),
            'api_key' => ENV('KEYSTONE_API_KEY'),
            'api_secret' => env('KEYSTONE_API_SECRET'),
            'cache' => env('KEYSTONE_API_CACHE', false),
        ],

    ],

    // Is this a keystone rest server install
    'server' => env('KEYSTONE_SERVER', false),
    'server_url' => env('KEYSTONE_SERVER_URL'),

];
