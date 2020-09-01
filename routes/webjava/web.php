<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 


$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);


$router->group(['middleware' => 'cors'], function () use ($router) {
    
    $router->get('menu', 'Webjava\WebController@getMenu');
    $router->get('gallery', 'Webjava\WebController@getGallery');
    $router->get('kontak', 'Webjava\WebController@getKontak');
    $router->get('page/{id}', 'Webjava\WebController@getPage');
    $router->get('news', 'Webjava\WebController@getNews');

    $router->post('login', 'AuthController@loginWebjava');
    $router->get('hash-pass', 'AuthController@hassPassWebjava');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('webjava/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('webjava/'.$filename); 
});


$router->group(['middleware' => 'auth:webjava'], function () use ($router) {
    

});



?>