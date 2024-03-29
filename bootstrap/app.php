<?php

require_once __DIR__ . '/../vendor/autoload.php';

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
    realpath(__DIR__ . '/../')
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


$app->alias('Storage', Illuminate\Support\Facades\Storage::class);

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

$app->configure('queue');


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
    'XSS' => App\Http\Middleware\XSS::class,
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
$app->register(App\Providers\EventServiceProvider::class);
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
$app->register(Milon\Barcode\BarcodeServiceProvider::class);



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
    require __DIR__ . '/../routes/web.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/approval'
], function ($router) {
    require __DIR__ . '/../routes/approval.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/gl'
], function ($router) {
    require __DIR__ . '/../routes/gl.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/apv'
], function ($router) {
    require __DIR__ . '/../routes/apv.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/silo'
], function ($router) {
    require __DIR__ . '/../routes/silo.php';
});




$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sppd'
], function ($router) {
    require __DIR__ . '/../routes/sppd.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/lapsaku'
], function ($router) {
    require __DIR__ . '/../routes/lapsaku.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/proyek'
], function ($router) {
    require __DIR__ . '/../routes/proyek.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/aset'
], function ($router) {
    require __DIR__ . '/../routes/aset.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/rtrw'
], function ($router) {
    require __DIR__ . '/../routes/rtrw.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sekolah'
], function ($router) {
    require __DIR__ . '/../routes/sekolah.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ts'
], function ($router) {
    require __DIR__ . '/../routes/ts.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sju'
], function ($router) {
    require __DIR__ . '/../routes/sju.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sju-api'
], function ($router) {
    require __DIR__ . '/../routes/sju/api.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/mobile-sekolah'
], function ($router) {
    require __DIR__ . '/../routes/mobilesekolah.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago'
], function ($router) {
    require __DIR__ . '/../routes/dago.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-auth'
], function ($router) {
    require __DIR__ . '/../routes/dago/auth.php';
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
    require __DIR__ . '/../routes/dago/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-master'
], function ($router) {
    require __DIR__ . '/../routes/dago/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-trans'
], function ($router) {
    require __DIR__ . '/../routes/dago/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dago-report'
], function ($router) {
    require __DIR__ . '/../routes/dago/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/midtrans'
], function ($router) {
    require __DIR__ . '/../routes/midtrans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-auth'
], function ($router) {
    require __DIR__ . '/../routes/toko/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-master'
], function ($router) {
    require __DIR__ . '/../routes/toko/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-trans'
], function ($router) {
    require __DIR__ . '/../routes/toko/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-report'
], function ($router) {
    require __DIR__ . '/../routes/toko/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/toko-dash'
], function ($router) {
    require __DIR__ . '/../routes/toko/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/portal'
], function ($router) {
    require __DIR__ . '/../routes/portal.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ginas'
], function ($router) {
    require __DIR__ . '/../routes/ginas.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-auth'
], function ($router) {
    require __DIR__ . '/../routes/sai/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-master'
], function ($router) {
    require __DIR__ . '/../routes/sai/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-trans'
], function ($router) {
    require __DIR__ . '/../routes/sai/trans.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-report'
], function ($router) {
    require __DIR__ . '/../routes/sai/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sai-dash'
], function ($router) {
    require __DIR__ . '/../routes/sai/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/warga'
], function ($router) {
    require __DIR__ . '/../routes/warga.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/apv-mobile'
], function ($router) {
    require __DIR__ . '/../routes/apv_mobile.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/webjava'
], function ($router) {
    require __DIR__ . '/../routes/webjava/web.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/webginas'
], function ($router) {
    require __DIR__ . '/../routes/webginas/web.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/wisata-auth'
], function ($router) {
    require __DIR__ . '/../routes/wisata/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/wisata-master'
], function ($router) {
    require __DIR__ . '/../routes/wisata/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/wisata-trans'
], function ($router) {
    require __DIR__ . '/../routes/wisata/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/wisata-report'
], function ($router) {
    require __DIR__ . '/../routes/wisata/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/wisata-dash'
], function ($router) {
    require __DIR__ . '/../routes/wisata/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/yakes-auth'
], function ($router) {
    require __DIR__ . '/../routes/yakes/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/yakes-master'
], function ($router) {
    require __DIR__ . '/../routes/yakes/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/yakes-trans'
], function ($router) {
    require __DIR__ . '/../routes/yakes/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/yakes-report'
], function ($router) {
    require __DIR__ . '/../routes/yakes/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/yakes-dash'
], function ($router) {
    require __DIR__ . '/../routes/yakes/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/yakes-api'
], function ($router) {
    require __DIR__ . '/../routes/yakes/api.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/admginas-auth'
], function ($router) {
    require __DIR__ . '/../routes/admginas/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/admginas-master'
], function ($router) {
    require __DIR__ . '/../routes/admginas/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/admginas-trans'
], function ($router) {
    require __DIR__ . '/../routes/admginas/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/admginas-report'
], function ($router) {
    require __DIR__ . '/../routes/admginas/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/admginas-dash'
], function ($router) {
    require __DIR__ . '/../routes/admginas/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/barber-auth'
], function ($router) {
    require __DIR__ . '/../routes/barber/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/barber-master'
], function ($router) {
    require __DIR__ . '/../routes/barber/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/barber-trans'
], function ($router) {
    require __DIR__ . '/../routes/barber/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/barber-report'
], function ($router) {
    require __DIR__ . '/../routes/barber/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/barber-dash'
], function ($router) {
    require __DIR__ . '/../routes/barber/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dev'
], function ($router) {
    require __DIR__ . '/../routes/dev.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/yptkug'
], function ($router) {
    require __DIR__ . '/../routes/yptkug.php';
});

/*
$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ypt'
], function ($router) {
    require __DIR__.'/../routes/ypt.php';
});
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ypt-auth'
], function ($router) {
    require __DIR__ . '/../routes/ypt/auth.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ypt-master'
], function ($router) {
    require __DIR__ . '/../routes/ypt/master.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ypt-trans'
], function ($router) {
    require __DIR__ . '/../routes/ypt/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ypt-report'
], function ($router) {
    require __DIR__ . '/../routes/ypt/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ypt-dash'
], function ($router) {
    require __DIR__ . '/../routes/ypt/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/siaga-dash'
], function ($router) {
    require __DIR__ . '/../routes/siaga/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/siaga-auth'
], function ($router) {
    require __DIR__ . '/../routes/siaga/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/siaga-master'
], function ($router) {
    require __DIR__ . '/../routes/siaga/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/siaga-report'
], function ($router) {
    require __DIR__ . '/../routes/siaga/report.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/siaga-trans'
], function ($router) {
    require __DIR__ . '/../routes/siaga/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/java-auth'
], function ($router) {
    require __DIR__ . '/../routes/java/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/java-master'
], function ($router) {
    require __DIR__ . '/../routes/java/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/java-trans'
], function ($router) {
    require __DIR__ . '/../routes/java/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/java-report'
], function ($router) {
    require __DIR__ . '/../routes/java/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/java-dash'
], function ($router) {
    require __DIR__ . '/../routes/java/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/esaku-auth'
], function ($router) {
    require __DIR__ . '/../routes/esaku/auth.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/esaku-master'
], function ($router) {
    require __DIR__ . '/../routes/esaku/master.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/esaku-trans'
], function ($router) {
    require __DIR__ . '/../routes/esaku/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/esaku-report'
], function ($router) {
    require __DIR__ . '/../routes/esaku/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/esaku-dash'
], function ($router) {
    require __DIR__ . '/../routes/esaku/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/admjava-auth'
], function ($router) {
    require __DIR__ . '/../routes/admjava/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/admjava-content'
], function ($router) {
    require __DIR__ . '/../routes/admjava/content.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/bangtel'
], function ($router) {
    require __DIR__ . '/../routes/bangtel.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simlog-auth'
], function ($router) {
    require __DIR__ . '/../routes/simlog/auth.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simlog-master'
], function ($router) {
    require __DIR__ . '/../routes/simlog/master.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simlog-trans'
], function ($router) {
    require __DIR__ . '/../routes/simlog/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simlog-report'
], function ($router) {
    require __DIR__ . '/../routes/simlog/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simlog-dash'
], function ($router) {
    require __DIR__ . '/../routes/simlog/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sdm'
], function ($router) {
    require __DIR__ . '/../routes/sdm.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/bdh-auth'
], function ($router) {
    require __DIR__ . '/../routes/bdh/auth.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/bdh-master'
], function ($router) {
    require __DIR__ . '/../routes/bdh/master.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/bdh-trans'
], function ($router) {
    require __DIR__ . '/../routes/bdh/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/bdh-report'
], function ($router) {
    require __DIR__ . '/../routes/bdh/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/bdh-dash'
], function ($router) {
    require __DIR__ . '/../routes/bdh/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-ypt'
], function ($router) {
    require __DIR__ . '/../routes/dash-ypt/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-ypt-master'
], function ($router) {
    require __DIR__ . '/../routes/dash-ypt/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-ypt-trans'
], function ($router) {
    require __DIR__ . '/../routes/dash-ypt/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-ypt-report'
], function ($router) {
    require __DIR__ . '/../routes/dash-ypt/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-ypt-dash'
], function ($router) {
    require __DIR__ . '/../routes/dash-ypt/dash.php';
});


$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-tarbak'
], function ($router) {
    require __DIR__ . '/../routes/dash-tarbak/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-tarbak-master'
], function ($router) {
    require __DIR__ . '/../routes/dash-tarbak/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-tarbak-trans'
], function ($router) {
    require __DIR__ . '/../routes/dash-tarbak/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-tarbak-report'
], function ($router) {
    require __DIR__ . '/../routes/dash-tarbak/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-tarbak-dash'
], function ($router) {
    require __DIR__ . '/../routes/dash-tarbak/dash.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sukka-auth'
], function ($router) {
    require __DIR__ . '/../routes/sukka/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sukka-master'
], function ($router) {
    require __DIR__ . '/../routes/sukka/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sukka-trans'
], function ($router) {
    require __DIR__ . '/../routes/sukka/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sukka-report'
], function ($router) {
    require __DIR__ . '/../routes/sukka/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/sukka-dash'
], function ($router) {
    require __DIR__ . '/../routes/sukka/dash.php';
});


// UI3
$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/ui3'
], function ($router) {
    require __DIR__ . '/../routes/ui3/api.php';
});

// DASH ITPLN
$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-itpln'
], function ($router) {
    require __DIR__ . '/../routes/dash-itpln/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-itpln-master'
], function ($router) {
    require __DIR__ . '/../routes/dash-itpln/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-itpln-trans'
], function ($router) {
    require __DIR__ . '/../routes/dash-itpln/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-itpln-report'
], function ($router) {
    require __DIR__ . '/../routes/dash-itpln/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/dash-itpln-dash'
], function ($router) {
    require __DIR__ . '/../routes/dash-itpln/dash.php';
});


//SIMKUG
$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simkug-auth'
], function ($router) {
    require __DIR__ . '/../routes/simkug/auth.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simkug-master'
], function ($router) {
    require __DIR__ . '/../routes/simkug/master.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simkug-trans'
], function ($router) {
    require __DIR__ . '/../routes/simkug/trans.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simkug-report'
], function ($router) {
    require __DIR__ . '/../routes/simkug/report.php';
});

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/simkug-dash'
], function ($router) {
    require __DIR__ . '/../routes/simkug/dash.php';
});


return $app;
