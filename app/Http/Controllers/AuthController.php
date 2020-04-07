<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

use  App\User;
use  App\Admin;
use  App\AdminYpt;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        //TAMBAHKAN BAGIAN INI
        $this->validate($request, [
            'nama' => 'required|string',
            'nik' => 'required',
            'password' => 'required|confirmed',
            'kode_lokasi' => 'required',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png',
            'klp_akses'=>'required',
            'kode_klp_menu'=>'required',
            'status_admin'=>'required',
            'menu_mobile'=>'required',
            'path_view'=>'required',
            'kode_menu_lab'=>'required'
        ]);
        
        try {
            //SEDIKIT TYPO DARI VARIABLE $filename, SEHINGGA PERBAHARUI SELURUH VARIABL TERKAIT
            $filename = null;
            if ($request->hasFile('foto')) {
                $filename = $request->nik . '.jpg';
                $file = $request->file('foto');
                $file->move(base_path('public/images'), $filename); //
            }
            
            // $user = new User;
            // $user->nama = $request->input('nama');
            // $user->nik = $request->input('nik');
            // $user->kode_lokasi = $request->input('kode_lokasi');
            // $user->kode_klp_menu = $request->input('kode_klp_menu');
            // $user->status_admin = $request->input('status_admin');
            // $user->klp_akses = $request->input('klp_akses');
            // $user->menu_mobile = $request->input('menu_mobile');            
            // $user->path_view = $request->input('path_view');
            
            // $user->kode_menu_lab = $request->input('kode_menu_lab');
            // $plainPassword = $request->input('password');
            // $user->pass = $plainPassword;
            // $user->password = app('hash')->make($plainPassword);

            // $user->save();

            // $karyawan = new karyawan;
            // $karyawan->nama = $request->input('nama');
            // $karyawan->nik = $request->input('nik');
            // $karyawan->kode_lokasi = $request->input('kode_lokasi');
            // $karyawan->alamat = '-';
            // $karyawan->jabatan = '-';
            // $karyawan->no_telp = '-';
            // $karyawan->email = '-';          
            // $karyawan->kode_pp = '-';
            // $karyawan->flag_aktif = '1';
            // $karyawan->foto = $filename;
            // $karyawan->save();
            //return successful response
            return response()->json(['message' => 'CREATED'], 201);

        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'User Registration Failed!'.$e], 409);
        }

    }

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

    public function loginYpt(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('ypt')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondYptWithToken($token);
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
            $success['message'] = "Hash Password berhasil disimpan ";
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
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	
    }

    public function hashPasswordYpt(){
        // $users = AdminYpt::all();
        // $users = AdminYpt::where('password', NULL)->paginate(10);
        // return count($users);
        DB::connection('sqlsrvypt')->beginTransaction();
        
        try {
            DB::connection('sqlsrvypt')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvypt')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password',NULL)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvypt')->commit();
            $success['status'] = false;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvypt')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }
}
