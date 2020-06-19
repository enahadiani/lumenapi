<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use  App\User;
use  App\Admin;
use  App\AdminYpt;
use  App\AdminSatpam;
use  App\AdminRtrw;

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

        return $this->respondWithToken($token,'user');
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

        return $this->respondWithToken($token,'admin');
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

        return $this->respondWithToken($token,'ypt');
    }

    public function loginYptKug(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('yptkug')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token,'yptkug');
    }

    public function loginSju(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('sju')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token,'sju');
    }

    public function loginRtrw(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('rtrw')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token,'rtrw');
    }

    public function loginTarbak(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('tarbak')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token,'tarbak');
    }

    public function loginSiswa(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('siswa')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token,'siswa');
    }

    public function loginDago(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('dago')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token,'dago');
    }

    public function loginToko(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (! $token = Auth::guard('toko')->setTTL(720)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token,'toko');
    }

    // $id_satpam = $request->input('qrcode');
        // $user = AdminSatpam::where('id_satpam', '=', $id_satpam)->first();
        // try { 
        //     // verify the credentials and create a token for the user
        //     if (!$token = JWTAuth::fromUser($user)) { 
        //         return response()->json(['message' => 'Unauthorized'], 401);
        //     } 
        // } catch (JWTException $e) { 
        //     // something went wrong 
        //     return response()->json(['message' => 'could_not_create_token'], 500); 
        // } 
    public function loginSatpam(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'qrcode' => 'required',
        ]);

        $user = AdminSatpam::where('id_satpam', '=', $request->qrcode)->first();
        $credentials = array('id_satpam' => $request->qrcode, 'password' => $user->pass);

        if (! $token = Auth::guard('satpam')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }else{
            if($data = Auth::guard('satpam')->user()){
                DB::connection('sqlsrvrtrw')->beginTransaction();
        
                try {
                    $ins = DB::connection('sqlsrvrtrw')->insert("insert into rt_satpam_log (id_satpam,kode_lokasi,tgl_log_in,flag_aktif) values ('$data->id_satpam','$data->kode_lokasi',getdate(),1) ");
                    DB::connection('sqlsrvrtrw')->commit();
                } catch (\Throwable $e) {
                    DB::connection('sqlsrvrtrw')->rollback();
                    $success['status'] = false;
                    $success['message'] = "Insert to satpam log error".$e;
                    return response()->json($success, 200); 
                }	

            }
        }
        return $this->respondWithToken($token,'satpam');
    }

    public function logoutSatpam(Request $request)
    {
          //validate incoming request 
        $token = Auth::guard('satpam')->getToken();
        try {
            if($data = Auth::guard('satpam')->user()){
                DB::connection('sqlsrvrtrw')->beginTransaction();
                try {
                    $ins = DB::connection('sqlsrvrtrw')->update("update rt_satpam_log set tgl_log_out=getdate(), flag_aktif=0 where id_satpam='$data->id_satpam' and kode_lokasi='$data->kode_lokasi' and flag_aktif='1' ");
                    DB::connection('sqlsrvrtrw')->commit();
                    Auth::guard('satpam')->invalidate($token);
                } catch (\Throwable $e) {
                    DB::connection('sqlsrvrtrw')->rollback();
                    $success['status'] = false;
                    $success['message'] = "Update to satpam log error".$e;
                    return response()->json($success, 200); 
                }	
                return response()->json([
                    'status' => true, 
                    'message'=> "User successfully logged out."
                ], 200);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json([
              'status' => false, 
              'message' => 'Failed to logout, please try again.'
            ], 200);
        }
        
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
            $success['status'] = true;
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
            $success['status'] = true;
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
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvypt')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordRtrw(){
        DB::connection('sqlsrvrtrw')->beginTransaction();
        
        try {
            DB::connection('sqlsrvrtrw')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvrtrw')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password',NULL)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvrtrw')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvrtrw')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordTarbak(){
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            DB::connection('sqlsrvtarbak')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvtarbak')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password',NULL)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordSiswa(){
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            DB::connection('sqlsrvtarbak')->table('sis_hakakses')->where('password', NULL)->orderBy('nik')->chunk(50, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvtarbak')->table('sis_hakakses')
                        ->where('nik', $user->nik)
                        ->where('password',NULL)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordYptKug(){
        DB::connection('sqlsrvyptkug')->beginTransaction();
        
        try {
            DB::connection('sqlsrvyptkug')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvyptkug')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password',NULL)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvyptkug')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvyptkug')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordSju(){
        DB::connection('sqlsrvsju')->beginTransaction();
        
        try {
            DB::connection('sqlsrvsju')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvsju')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password',NULL)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvsju')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvsju')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordDago(){
        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            DB::connection('sqlsrvdago')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvdago')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password',NULL)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvdago')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordToko(){
        $users = Admin::all();
        DB::connection('tokoaws')->beginTransaction();
        
        try {
            DB::connection('tokoaws')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('tokoaws')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('tokoaws')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('tokoaws')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	
    }

    public function hashPasswordByNIK($db,$table,$nik){
        DB::connection($db)->beginTransaction();
        
        try {
            
            $res = DB::connection($db)->select ("select pass from $table where nik='$nik' ");
            $res = json_decode(json_encode($res),true);
            $password = $res[0]['pass'];
            DB::connection($db)->table($table)
            ->where('nik', $nik)
            ->update(['password' => app('hash')->make($password)]);
                
            DB::connection($db)->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection($db)->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPasswordCostum($db,$table,$top,$kode_pp=null){
        DB::connection($db)->beginTransaction();
        
        try {
        
            if($kode_pp != "" OR $kode_pp != NULL){
                $filter = " and kode_pp='$kode_pp' ";
            }else{
                $filter = "";
            }
            $users = DB::connection($db)->select("select top $top nik,pass from $table where isnull(password,'-')= '-' order by nik ");

            foreach ($users as $user) {
                DB::connection($db)->table($table)
                        ->where('nik', $user->nik)
                        ->where('password',NULL)
                        ->update(['password' => app('hash')->make($user->pass)]);
            }
                
            DB::connection($db)->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection($db)->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function hashPassTable($db,$table){
        $users = AdminSatpam::all();
        DB::connection('sqlsrvrtrw')->beginTransaction();
        
        try {
            DB::connection('sqlsrvrtrw')->table('rt_satpam')->orderBy('id_satpam')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvrtrw')->table('rt_satpam')
                        ->where('id_satpam', $user->id_satpam)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvrtrw')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }
}
