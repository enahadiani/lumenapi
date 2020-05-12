<?php

return [
    'connections' => [
        'default' => env('DB_CONNECTION', 'sqlsrv'),
        'sqlite' => [
            'driver' => env('DB_CONNECTION','sqlite'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],
    
        'mysql' => [
            'driver' => env('DB_CONNECTION','mysql'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    
        'mysql2' => [
            'driver' => env('DB_CONNECTION_2','mysql'),
            'host' => env('DB_HOST_2', '127.0.0.1'),
            'port' => env('DB_PORT_2', '3306'),
            'database' => env('DB_DATABASE_2', 'forge'),
            'username' => env('DB_USERNAME_2', 'forge'),
            'password' => env('DB_PASSWORD_2', ''),
            'unix_socket' => env('DB_SOCKET_2', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],    
        
        'sqlsrv' => [
            'driver' => env('DB_CONNECTION','sqlsrv'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],

        'sqlsrv2' => [
            'driver' => env('DB_CONNECTION2','sqlsrv'),
            'host' => env('DB_HOST2', 'localhost'),
            'port' => env('DB_PORT2', '1433'),
            'database' => env('DB_DATABASE2', 'forge'),
            'username' => env('DB_USERNAME2', 'forge'),
            'password' => env('DB_PASSWORD2', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvypt' => [
            'driver' => env('DB_CONNECTION3','sqlsrv'),
            'host' => env('DB_HOST3', 'localhost'),
            'port' => env('DB_PORT3', '1433'),
            'database' => env('DB_DATABASE3', 'forge'),
            'username' => env('DB_USERNAME3', 'forge'),
            'password' => env('DB_PASSWORD3', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvrtrw' => [
            'driver' => env('DB_CONNECTION4','sqlsrv'),
            'host' => env('DB_HOST4', 'localhost'),
            'port' => env('DB_PORT4', '1433'),
            'database' => env('DB_DATABASE4', 'forge'),
            'username' => env('DB_USERNAME4', 'forge'),
            'password' => env('DB_PASSWORD4', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvtarbak' => [
            'driver' => env('DB_CONNECTION5','sqlsrv'),
            'host' => env('DB_HOST5', 'localhost'),
            'port' => env('DB_PORT5', '1433'),
            'database' => env('DB_DATABASE5', 'forge'),
            'username' => env('DB_USERNAME5', 'forge'),
            'password' => env('DB_PASSWORD5', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
    
    ],
    'migrations' => 'migrations',
 
    'redis' => [
 
        'client' => 'predis',
 
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
 
    ],
 

];