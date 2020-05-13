<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'user' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'admin' => [
            'driver' => 'jwt',
            'provider' => 'admin',
        ],
        'ypt' => [
            'driver' => 'jwt',
            'provider' => 'adminypt',
        ],
        'rtrw' => [
            'driver' => 'jwt',
            'provider' => 'adminrtrw',
        ],
        'tarbak' => [
            'driver' => 'jwt',
            'provider' => 'admintarbak',
        ],
        'yptkug' => [
            'driver' => 'jwt',
            'provider' => 'adminyptkug',
        ],
        'sju' => [
            'driver' => 'jwt',
            'provider' => 'adminsju',
        ]
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => \App\User::class
        ],
        'admin' => [
            'driver' => 'eloquent',
            'model' => \App\Admin::class
        ],
        'adminypt' => [
            'driver' => 'eloquent',
            'model' => \App\AdminYpt::class
        ],
        'adminrtrw' => [
            'driver' => 'eloquent',
            'model' => \App\AdminRtrw::class
        ],
        'admintarbak' => [
            'driver' => 'eloquent',
            'model' => \App\AdminTarbak::class
        ],
        'adminyptkug' => [
            'driver' => 'eloquent',
            'model' => \App\AdminYptKug::class
        ],
        'adminsju' => [
            'driver' => 'eloquent',
            'model' => \App\AdminSju::class
        ]
    ]
];