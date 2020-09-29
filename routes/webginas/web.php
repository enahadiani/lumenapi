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
    
    $router->get('menu', 'Webginas\WebController@getMenu');
    $router->get('home', 'Webginas\WebController@getHome');
    $router->get('gallery', 'Webginas\WebController@getGallery');
    $router->get('kontak', 'Webginas\WebController@getKontak');
    $router->get('page/{id}', 'Webginas\WebController@getPage');
    $router->get('news', 'Webginas\WebController@getNews');
    $router->get('article', 'Webginas\WebController@getArticle');
    $router->get('read-item', 'Webginas\WebController@readItem');
    $router->get('video', 'Webginas\WebController@getVideo');
    $router->get('watch/{id}', 'Webginas\WebController@getWatch');

    $router->post('login', 'AuthController@loginWebginas');
    $router->get('hash-pass', 'AuthController@hassPassWebginas');
    $router->post('lab-log/{id}', 'AuthController@simpanLog');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('webginas/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('webginas/'.$filename); 
});


$router->group(['middleware' => 'auth:webginas'], function () use ($router) {
    

});



?>