<?php

return [

	// Default connection
	'default' => env('KEYSTONE_DRIVER', 'default'),


	// Keystone connections
	'connections' => [
		
		'default' => [
			'driver' => 'native',
			'host' => env('KEYSTONE_HOST', '127.0.0.1'),
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
			'url' => env('KEYSTONE_URL', 'http://keystone.xendev1.dynatronsoftware.com'),
			'secret' => env('KEYSTONE_SECRET', 'xyz'),
			'cache' => env('KEYSTONE_CACHE', false),
		],

	],

	// Is this a keystone rest server install
	'server' => env('KEYSTONE_SERVER', false),

];
