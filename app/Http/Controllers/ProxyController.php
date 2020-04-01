<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

class ProxyController extends Controller
{
    public function callService(Request $request)
    {
          //validate incoming request 
            
            
            $service = $request->input("service");
            $method = $request->input("method");
            $params = $request->input("params");
            $params = json_decode($params);
            if ($params == null){
                $params = array();
            }
            $service = str_replace("_","\\",$service);
            $app = app();
            $controller = $app->make('\App\Http\Controllers\\'. $service);
            // eval("\$handler = \$controller->$method($params);");//eval("\$handler = new services_".$service."();");
            $handlerFunc = array($controller, $method);
            $result = call_user_func_array($handlerFunc, $params);

            return  $result;
    }
}
