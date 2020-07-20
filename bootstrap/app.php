<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

// $app = new Laravel\Lumen\Application(
//     dirname(__DIR__)
// );



// $app->withFacades();

// $app->withEloquent();
$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

$app->instance('path.storage', app()->basePath() . DIRECTORY_SEPARATOR . 'storage');

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');

$app->configure('filesystems');


$app->alias('Storage',Illuminate\Support\Facades\Storage::class);

// class_alias('Illuminate\Support\Facades\Storage', 'Storage');

$app->configure('mail');
$app->alias('mailer', Illuminate\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);


$app->configure('excel');
$app->alias('Excel', Maatwebsite\Excel\Facades\Excel::class);

// $app->configure('swagger-lume');

$app->configure('dompdf');

//$app->configure('pretty-routes');


$app->alias('QrCode', SimpleSoftwareIO\QrCode\Facade::class);


$app->configure('services');
$app->alias('Socialite', Laravel\Socialite\Facades\Socialite::class);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//     // App\Http\Middleware\ExampleMiddleware::class
//     App\Http\Middleware\CorsMiddleware::class
// ]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'cors' => App\Http\Middleware\CorsMiddleware::class,
    'jwt.portal' => App\Http\Middleware\JwtMiddleware::class,
]);


/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);
$app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);

$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);
// $app->register(SwaggerLume\ServiceProvider::class);
$app->register(Barryvdh\DomPDF\ServiceProvider::class);
// $app->register(Collective\Html\HtmlServiceProvider::class);
//$app->register(PrettyRoutes\ServiceProvider::class);
$app->register(SimpleSoftwareIO\QrCode\ServiceProvider::class);

$app->register(Laravel\Socialite\SocialiteServiceProvider::class);



/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/approval'
], function ($router) {
    require __DIR__.'/../routes/approval.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/gl'
], function ($router) {
    require __DIR__.'/../routes/gl.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/apv'
], function ($router) {
    require __DIR__.'/../routes/apv.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ypt'
], function ($router) {
    require __DIR__.'/../routes/ypt.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sppd'
], function ($router) {
    require __DIR__.'/../routes/sppd.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/lapsaku'
], function ($router) {
    require __DIR__.'/../routes/lapsaku.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/proyek'
], function ($router) {
    require __DIR__.'/../routes/proyek.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/aset'
], function ($router) {
    require __DIR__.'/../routes/aset.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/rtrw'
], function ($router) {
    require __DIR__.'/../routes/rtrw.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sekolah'
], function ($router) {
    require __DIR__.'/../routes/sekolah.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sju'
], function ($router) {
    require __DIR__.'/../routes/sju.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/mobile_sekolah'
], function ($router) {
    require __DIR__.'/../routes/mobilesekolah.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago'
], function ($router) {
    require __DIR__.'/../routes/dago.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-auth'
], function ($router) {
    require __DIR__.'/../routes/dago/auth.php';
});

// $app->router->group([
//     'namespace' => 'App\Http\Controllers',
//     'prefix' => 'api/dago-out'
// ], function ($router) {
//     require __DIR__.'/../routes/dago/out.php';
// });

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-dash'
], function ($router) {
    require __DIR__.'/../routes/dago/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-master'
], function ($router) {
    require __DIR__.'/../routes/dago/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-trans'
], function ($router) {
    require __DIR__.'/../routes/dago/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-report'
], function ($router) {
    require __DIR__.'/../routes/dago/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/midtrans'
], function ($router) {
    require __DIR__.'/../routes/midtrans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-auth'
], function ($router) {
    require __DIR__.'/../routes/toko/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-master'
], function ($router) {
    require __DIR__.'/../routes/toko/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-trans'
], function ($router) {
    require __DIR__.'/../routes/toko/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-report'
], function ($router) {
    require __DIR__.'/../routes/toko/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-dash'
], function ($router) {
    require __DIR__.'/../routes/toko/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/portal'
], function ($router) {
    require __DIR__.'/../routes/portal.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ginas'
], function ($router) {
    require __DIR__.'/../routes/ginas.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-auth'
], function ($router) {
    require __DIR__.'/../routes/sai/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-master'
], function ($router) {
    require __DIR__.'/../routes/sai/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-trans'
], function ($router) {
    require __DIR__.'/../routes/sai/trans.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-report'
], function ($router) {
    require __DIR__.'/../routes/sai/report.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-dash'
], function ($router) {
    require __DIR__.'/../routes/sai/dash.php';
});
return $app;
