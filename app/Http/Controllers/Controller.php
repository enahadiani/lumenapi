<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Auth;
class Controller extends BaseController
{

    protected function respondWithToken($token,$auth,$expired = 60)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard($auth)->factory()->getTTL() * $expired
        ], 200);
    }

    

    protected function respondWithTokenExtraData($token,$auth,$expired = 60,$data = [])
    {
        $dt_array = [
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard($auth)->factory()->getTTL() * $expired
        ];
        $dt_array = array_merge($dt_array,$data);
        return response()->json($dt_array, 200);
    }

}
