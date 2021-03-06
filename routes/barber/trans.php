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
   //kunjungan
   $router->post('kunj','Barber\KunjController@store');
   $router->get('getNoBukti','Barber\KunjController@getNoBukti');                    

   //closing
   $router->post('closing','Barber\CloseController@store');
   $router->get('getNoClose','Barber\CloseController@getNoClose');                    
   $router->get('getKunj','Barber\CloseController@getKunj');                    

});



?>