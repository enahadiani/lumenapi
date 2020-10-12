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
    //tanggal server
    $router->get('getTglServer','Barber\FilterController@getTglServer');     
    //periode input
    $router->get('getPerInput','Barber\FilterController@getPerInput');     

    //paket
    $router->get('paket','Barber\PaketController@index');
    $router->post('paket','Barber\PaketController@store');
    $router->put('paket','Barber\PaketController@update');
    $router->delete('paket','Barber\PaketController@destroy');    
    $router->get('listPaketAktif','Barber\PaketController@listPaketAktif');         
    $router->get('cariPaketAktif','Barber\PaketController@cariPaketAktif');

    //barber
    $router->get('barber','Barber\BarberController@index');
    $router->post('barber','Barber\BarberController@store');
    $router->put('barber','Barber\BarberController@update');
    $router->delete('barber','Barber\BarberController@destroy');    
    $router->get('listBarberAktif','Barber\PaketController@listBarberAktif');         
    $router->get('cariBarberAktif','Barber\PaketController@cariBarberAktif');

    //cust
    $router->get('cust','Barber\CustController@index');
    $router->post('cust','Barber\CustController@store');
    $router->put('cust','Barber\CustController@update');
    $router->delete('cust','Barber\CustController@destroy');  
    $router->get('listCustAktif','Barber\PaketController@listCustAktif');         
    $router->get('cariCustAktif','Barber\PaketController@cariCustAktif');  


});



?>