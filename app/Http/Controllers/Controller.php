<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Auth;
class Controller extends BaseController
{
   
    protected function respondWithToken($token,$auth)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard($auth)->factory()->getTTL() * 60
        ], 200);
    }
}
