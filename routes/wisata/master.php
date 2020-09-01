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


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
   
    //Vendor
    $router->get('vendor','Toko\VendorController@index');
    $router->post('vendor','Toko\VendorController@store');
    $router->put('vendor','Toko\VendorController@update');
    $router->delete('vendor','Toko\VendorController@destroy');
    $router->get('vendor-akun','Toko\VendorController@getAkun');

});



?>