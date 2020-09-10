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
        ],
        'siswa' => [
            'driver' => 'jwt',
            'provider' => 'adminsiswa',
        ],
        'dago' => [
            'driver' => 'jwt',
            'provider' => 'admindago',
        ],
        'toko' => [
            'driver' => 'jwt',
            'provider' => 'admintoko',
        ],
        'ginas' => [
            'driver' => 'jwt',
            'provider' => 'adminginas',
        ],
        'satpam' => [
            'driver' => 'jwt',
            'provider' => 'satpam',
        ],
        'warga' => [
            'driver' => 'jwt',
            'provider' => 'warga',
        ],
        'silo' => [
            'driver' => 'jwt',
            'provider' => 'silo',
        ],
        'yakes' => [
            'driver' => 'jwt',
            'provider' => 'yakes',
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
        ],
        'adminsiswa' => [
            'driver' => 'eloquent',
            'model' => \App\AdminSiswa::class
        ],
        'admindago' => [
            'driver' => 'eloquent',
            'model' => \App\AdminDago::class
        ],
        'admintoko' => [
            'driver' => 'eloquent',
            'model' => \App\AdminToko::class
        ],
        'adminginas' => [
            'driver' => 'eloquent',
            'model' => \App\AdminGinas::class
        ],
        'satpam' => [
            'driver' => 'eloquent',
            'model' => \App\AdminSatpam::class
        ],
        'warga' => [
            'driver' => 'eloquent',
            'model' => \App\AdminWarga::class
        ],
        'silo' => [
            'driver' => 'eloquent',
            'model' => \App\AdminSilo::class
        ],
        'yakes' => [
            'driver' => 'eloquent',
            'model' => \App\AdminYakes::class
        ]
    ]
];