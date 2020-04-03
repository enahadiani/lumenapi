<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

use  App\User;
use  App\Admin;

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

        if (! $token = Auth::guard('user')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function loginAdmin(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('admin')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondAdminWithToken($token);
    }

    public function hashPassword(){
        $users = User::all();
        DB::connection('sqlsrv')->beginTransaction();
        
        try {
            DB::connection('sqlsrv')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrv')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrv')->commit();
            $success['status'] = false;
            $success['message'] = "Hash Password berhasil disimpan ".$e;
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordAdmin(){
        $users = Admin::all();
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            DB::connection('sqlsrv2')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrv2')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrv2')->commit();
            $success['status'] = false;
            $success['message'] = "Hash Password berhasil disimpan ".$e;
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	
    }
}
