<?php

return [
    'connections' => [
        'default' => env('DB_CONNECTION', 'sqlsrv'),
        'sqlite' => [
            'driver' => env('DB_CONNECTION', 'sqlite'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => env('DB_CONNECTION', 'mysql'),
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
            'driver' => env('DB_CONNECTION_2', 'mysql'),
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
            'driver' => env('DB_CONNECTION', 'sqlsrv'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrv2' => [
            'driver' => env('DB_CONNECTION2', 'sqlsrv'),
            'host' => env('DB_HOST2', 'localhost'),
            'port' => env('DB_PORT2', '1433'),
            'database' => env('DB_DATABASE2', 'forge'),
            'username' => env('DB_USERNAME2', 'forge'),
            'password' => env('DB_PASSWORD2', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvypt' => [
            'driver' => env('DB_CONNECTION3', 'sqlsrv'),
            'host' => env('DB_HOST3', 'localhost'),
            'port' => env('DB_PORT3', '1433'),
            'database' => env('DB_DATABASE3', 'forge'),
            'username' => env('DB_USERNAME3', 'forge'),
            'password' => env('DB_PASSWORD3', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvrtrw' => [
            'driver' => env('DB_CONNECTION4', 'sqlsrv'),
            'host' => env('DB_HOST4', 'localhost'),
            'port' => env('DB_PORT4', '1433'),
            'database' => env('DB_DATABASE4', 'forge'),
            'username' => env('DB_USERNAME4', 'forge'),
            'password' => env('DB_PASSWORD4', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvtarbak' => [
            'driver' => env('DB_CONNECTION5', 'sqlsrv'),
            'host' => env('DB_HOST5', 'localhost'),
            'port' => env('DB_PORT5', '1433'),
            'database' => env('DB_DATABASE5', 'forge'),
            'username' => env('DB_USERNAME5', 'forge'),
            'password' => env('DB_PASSWORD5', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvyptkug' => [
            'driver' => env('DB_CONNECTION6', 'sqlsrv'),
            'host' => env('DB_HOST6', 'localhost'),
            'port' => env('DB_PORT6', '1433'),
            'database' => env('DB_DATABASE6', 'forge'),
            'username' => env('DB_USERNAME6', 'forge'),
            'password' => env('DB_PASSWORD6', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvsju' => [
            'driver' => env('DB_CONNECTION7', 'sqlsrv'),
            'host' => env('DB_HOST7', 'localhost'),
            'port' => env('DB_PORT7', '1433'),
            'database' => env('DB_DATABASE7', 'forge'),
            'username' => env('DB_USERNAME7', 'forge'),
            'password' => env('DB_PASSWORD7', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvdago' => [
            'driver' => env('DB_CONNECTION8', 'sqlsrv'),
            'host' => env('DB_HOST8', 'localhost'),
            'port' => env('DB_PORT8', '1433'),
            'database' => env('DB_DATABASE8', 'forge'),
            'username' => env('DB_USERNAME8', 'forge'),
            'password' => env('DB_PASSWORD8', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'tokoaws' => [
            'driver' => env('DB_CONNECTION9', 'sqlsrv'),
            'host' => env('DB_HOST9', 'localhost'),
            'port' => env('DB_PORT9', '1433'),
            'database' => env('DB_DATABASE9', 'forge'),
            'username' => env('DB_USERNAME9', 'forge'),
            'password' => env('DB_PASSWORD9', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'sqlsrvginas' => [
            'driver' => env('DB_CONNECTION10', 'sqlsrv'),
            'host' => env('DB_HOST10', 'localhost'),
            'port' => env('DB_PORT10', '1433'),
            'database' => env('DB_DATABASE10', 'forge'),
            'username' => env('DB_USERNAME10', 'forge'),
            'password' => env('DB_PASSWORD10', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbsilo' => [
            'driver' => env('DB_CONNECTION11', 'sqlsrv'),
            'host' => env('DB_HOST11', 'localhost'),
            'port' => env('DB_PORT11', '1433'),
            'database' => env('DB_DATABASE11', 'forge'),
            'username' => env('DB_USERNAME11', 'forge'),
            'password' => env('DB_PASSWORD11', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbnewsilo' => [
            'driver' => env('DB_CONNECTION110', 'sqlsrv'),
            'host' => env('DB_HOST110', 'localhost'),
            'port' => env('DB_PORT110', '1433'),
            'database' => env('DB_DATABASE110', 'forge'),
            'username' => env('DB_USERNAME110', 'forge'),
            'password' => env('DB_PASSWORD110', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbsaife' => [
            'driver' => env('DB_CONNECTION12', 'sqlsrv'),
            'host' => env('DB_HOST12', 'localhost'),
            'port' => env('DB_PORT12', '1433'),
            'database' => env('DB_DATABASE12', 'forge'),
            'username' => env('DB_USERNAME12', 'forge'),
            'password' => env('DB_PASSWORD12', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbsapkug' => [
            'driver' => env('DB_CONNECTION13', 'sqlsrv'),
            'host' => env('DB_HOST13', 'localhost'),
            'port' => env('DB_PORT13', '1433'),
            'database' => env('DB_DATABASE13', 'forge'),
            'username' => env('DB_USERNAME13', 'forge'),
            'password' => env('DB_PASSWORD13', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbaset' => [
            'driver' => env('DB_CONNECTION14', 'sqlsrv'),
            'host' => env('DB_HOST14', 'localhost'),
            'port' => env('DB_PORT14', '1433'),
            'database' => env('DB_DATABASE14', 'forge'),
            'username' => env('DB_USERNAME14', 'forge'),
            'password' => env('DB_PASSWORD14', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbsiaga' => [
            'driver' => env('DB_CONNECTION15', 'sqlsrv'),
            'host' => env('DB_HOST15', 'localhost'),
            'port' => env('DB_PORT15', '1433'),
            'database' => env('DB_DATABASE15', 'forge'),
            'username' => env('DB_USERNAME15', 'forge'),
            'password' => env('DB_PASSWORD15', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbbangtelindo' => [
            'driver' => env('DB_CONNECTION18', 'sqlsrv'),
            'host' => env('DB_HOST18', 'localhost'),
            'port' => env('DB_PORT18', '1433'),
            'database' => env('DB_DATABASE18', 'forge'),
            'username' => env('DB_USERNAME18', 'forge'),
            'password' => env('DB_PASSWORD18', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbui3' => [
            'driver' => env('DB_CONNECTIONui3', 'sqlsrv'),
            'host' => env('DB_HOSTui3', 'localhost'),
            'port' => env('DB_PORTui3', '1433'),
            'database' => env('DB_DATABASEui3', 'forge'),
            'username' => env('DB_USERNAMEui3', 'forge'),
            'password' => env('DB_PASSWORDui3', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbpln' => [
            'driver' => env('DB_CONNECTIONpln', 'sqlsrv'),
            'host' => env('DB_HOSTpln', 'localhost'),
            'port' => env('DB_PORTpln', '1433'),
            'database' => env('DB_DATABASEpln', 'forge'),
            'username' => env('DB_USERNAMEpln', 'forge'),
            'password' => env('DB_PASSWORDpln', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        'dbsimkug' => [
            'driver' => env('DB_CONNECTIONsimkug', 'sqlsrv'),
            'host' => env('DB_HOSTsimkug', 'localhost'),
            'port' => env('DB_PORTsimkug', '1433'),
            'database' => env('DB_DATABASEsimkug', 'forge'),
            'username' => env('DB_USERNAMEsimkug', 'forge'),
            'password' => env('DB_PASSWORDsimkug', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ]

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
