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
    // Helper 
    $router->get('customer-akun','Java\CustomerController@getAkun');
    $router->get('customer-check','Java\CustomerController@checkCustomer');
    $router->get('vendor-akun','Java\VendorController@getAkun');
    $router->get('vendor-check','Java\VendorController@checkVendor');

    //Customer
    $router->get('customer','Java\CustomerController@index');
    $router->post('customer','Java\CustomerController@store');
    $router->put('customer','Java\CustomerController@update');
    $router->delete('customer','Java\CustomerController@destroy');

    //Vendor
    $router->get('vendor','Java\VendorController@index');
    $router->post('vendor','Java\VendorController@store');
    $router->put('vendor','Java\VendorController@update');
    $router->delete('vendor','Java\VendorController@destroy');

    // Bank
    $router->get('bank','Java\BankController@index');
    $router->post('bank','Java\BankController@store');
    $router->put('bank','Java\BankController@update');
    $router->delete('bank','Java\BankController@destroy');

});



?>