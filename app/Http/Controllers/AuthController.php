<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use  App\User;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     //validate incoming request 
    //     $this->validate($request, [
    //         'nama' => 'required|string',
    //         'nik' => 'required|unique:hakakses',
    //         'password' => 'required|confirmed',
    //         'kode_lokasi' => 'required'
    //     ]);

    //     try {

    //         $user = new User;
    //         $user->nama = $request->input('nama');
    //         $user->nik = $request->input('nik');
    //         $user->kode_lokasi = $request->input('kode_lokasi');
    //         $user->kode_klp_menu = $request->input('kode_klp_menu');
    //         $user->status_admin = $request->input('status_admin');
    //         $user->klp_akses = $request->input('klp_akses');
    //         $user->menu_mobile = $request->input('menu_mobile');            
    //         $user->path_view = $request->input('path_view');
            
    //         $user->kode_menu_lab = $request->input('kode_menu_lab');
    //         $plainPassword = $request->input('password');
    //         $user->password = app('hash')->make($plainPassword);

    //         $user->save();

    //         //return successful response
    //         return response()->json(['user' => $user, 'message' => 'CREATED'], 201);

    //     } catch (\Exception $e) {
    //         //return error message
    //         return response()->json(['message' => 'User Registration Failed!'.$e], 409);
    //     }

    // }

    public function login(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }
}
