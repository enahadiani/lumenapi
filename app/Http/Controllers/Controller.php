<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Auth;
class Controller extends BaseController
{
    /**
     * @OA\Info(
     *   title="Example API",
     *   version="1.0",
     *   @OA\Contact(
     *     email="support@example.com",
     *     name="Support Team"
     *   )
     * )
     */
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
}
