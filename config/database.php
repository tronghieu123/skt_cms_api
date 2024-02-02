<?php

use Illuminate\Support\Str;
return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the DB_GATEWAY_DATABASE
     library.
    |
    */

//    'default' => env('DB_GATEWAY_CONNECTION', 'sky_cms'),
    'default' => 'sky_cms',

    /*
    |--------------------------------------------------------------------------
    | DB_GATEWAY_DATABASE
     Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

//        'sqlite' => [
//            'driver' => 'sqlite',
//            'url' => env('DATABASE_URL'),
//            'database' => env('DB_GATEWAY_DATABASE
//            ', database_path('database.sqlite')),
//            'prefix' => '',
//            'foreign_key_constraints' => env('DB_GATEWAY_FOREIGN_KEYS', true),
//        ],
//
//        'mysql' => [
//            'driver' => 'mysql',
//            'url' => env('DATABASE_URL'),
//            'host' => env('DB_GATEWAY_HOST', '127.0.0.1'),
//            'port' => env('DB_GATEWAY_PORT', '3306'),
//            'database' => env('DB_GATEWAY_DATABASE', 'forge'),
//            'username' => env('DB_GATEWAY_USERNAME', 'forge'),
//            'password' => env('DB_GATEWAY_PASSWORD', ''),
//            'unix_socket' => env('DB_GATEWAY_SOCKET', ''),
//            'charset' => 'utf8mb4',
//            'collation' => 'utf8mb4_unicode_ci',
//            'prefix' => '',
//            'prefix_indexes' => true,
//            'strict' => true,
//            'engine' => null,
//            'options' => extension_loaded('pdo_mysql') ? array_filter([
//                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
//            ]) : [],
//        ],
//
//        'pgsql' => [
//            'driver' => 'pgsql',
//            'url' => env('DATABASE_URL'),
//            'host' => env('DB_GATEWAY_HOST', '127.0.0.1'),
//            'port' => env('DB_GATEWAY_PORT', '5432'),
//            'database' => env('DB_GATEWAY_DATABASE', 'forge'),
//            'username' => env('DB_GATEWAY_USERNAME', 'forge'),
//            'password' => env('DB_GATEWAY_PASSWORD', ''),
//            'charset' => 'utf8',
//            'prefix' => '',
//            'prefix_indexes' => true,
//            'search_path' => 'public',
//            'sslmode' => 'prefer',
//        ],
//
//        'sqlsrv' => [
//            'driver' => 'sqlsrv',
//            'url' => env('DATABASE_URL'),
//            'host' => env('DB_GATEWAY_HOST', 'localhost'),
//            'port' => env('DB_GATEWAY_PORT', '1433'),
//            'database' => env('DB_GATEWAY_DATABASE', 'forge'),
//            'username' => env('DB_GATEWAY_USERNAME', 'forge'),
//            'password' => env('DB_GATEWAY_PASSWORD', ''),
//            'charset' => 'utf8',
//            'prefix' => '',
//            'prefix_indexes' => true,
//            // 'encrypt' => env('DB_GATEWAY_ENCRYPT', 'yes'),
//            // 'trust_server_certificate' => env('DB_GATEWAY_TRUST_SERVER_CERTIFICATE', 'false'),
//        ],

        'sky_cms' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_cms',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'sky_gateway' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_gateway',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'config' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'config',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'fnb_product' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'fnb_product',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'fnb_store' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'fnb_store',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'ims' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'ims',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'local' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'local',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'sky' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'sky_booking' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_booking',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'sky_driver' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_driver',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'sky_partner' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_partner',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'sky_user' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_user',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'sky_voucher' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_voucher',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
        'sky_payment' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_payment',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],

        'sky_firebase' => [
            'driver' => 'mongodb',
            'host' => 'sd108208.server.idn.vn',
            'port' => 27017,
            'database' => 'sky_firebase',
            'username' => 'skytech',
            'password' => '2prMywH8UhxBvt',
            'options' => [
//                'appname' => 'homestead',
                'db' => 'admin'
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
