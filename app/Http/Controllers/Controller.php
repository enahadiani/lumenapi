<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Auth;
class Controller extends BaseController
{
   
    protected function respondWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('user')->factory()->getTTL() * 60
        ], 200);
    }

    protected function respondAdminWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('admin')->factory()->getTTL() * 60
        ], 200);
    }

    protected function respondYptWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('ypt')->factory()->getTTL() * 60
        ], 200);
    }

    protected function respondRtrwWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('rtrw')->factory()->getTTL() * 60
        ], 200);
    }

    protected function respondTarbakWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('tarbak')->factory()->getTTL() * 60
        ], 200);
    }

    protected function respondSiswaWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('siswa')->factory()->getTTL() * 60
        ], 200);
    }

    protected function respondYptKugWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('yptkug')->factory()->getTTL() * 60
        ], 200);
    }

    protected function respondSjuWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('sju')->factory()->getTTL() * 60
        ], 200);
    }

    protected function respondDagoWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'message' => 'success',
            'expires_in' => Auth::guard('dago')->factory()->getTTL() * 60
        ], 200);
    }
}
