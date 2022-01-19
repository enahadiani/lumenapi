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
use  App\AdminWarga;
use  App\AdminSilo;
use  App\AdminAset;
use  App\AdminBangtel;
use App\AdminNewSilo;

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
            'klp_akses' => 'required',
            'kode_klp_menu' => 'required',
            'status_admin' => 'required',
            'menu_mobile' => 'required',
            'path_view' => 'required',
            'kode_menu_lab' => 'required'
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
            return response()->json(['message' => 'User Registration Failed!' . $e], 409);
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

        if (!$token = Auth::guard('user')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'user');
    }

    public function loginAdmin(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('admin')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'admin');
    }

    public function loginAdminAset(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('aset')->setTTL(10080)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'aset');
    }

    public function loginYpt(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('ypt')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'ypt');
    }

    public function loginYptKug(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('yptkug')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'yptkug');
    }

    public function loginSju(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('sju')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if (isset($request->id_device)) {

                DB::connection('sqlsrvsju')->table('karyawan')
                    ->where('nik', $request->nik)
                    ->update(['id_device' => $request->id_device]);
            }
        }

        return $this->respondWithToken($token, 'sju');
    }

    public function loginRtrw(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('rtrw')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'rtrw');
    }

    public function loginTarbak(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('tarbak')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'tarbak');
    }

    public function loginSiswa(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('siswa')->setTTL(10080)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if (isset($request->id_device)) {
                // if(Auth::guard('siswa')->user()->status_login == "S"){
                //     DB::connection('sqlsrvtarbak')->table('sis_siswa')
                //     ->where('nis', $request->nik)
                //     ->update(['id_device' => $request->id_device]);
                // }else if(Auth::guard('siswa')->user()->status_login == "G"){
                //     DB::connection('sqlsrvtarbak')->table('sis_guru')
                //     ->where('nik', $request->nik)
                //     ->update(['id_device' => $request->id_device]);
                // }
                $kode_lokasi = Auth::guard('siswa')->user()->kode_lokasi;
                $kode_pp = Auth::guard('siswa')->user()->kode_pp;
                $cek = DB::connection('sqlsrvtarbak')->select("select count(id_device) as jum from users_device where nik='$request->nik'  ");
                if (count($cek) > 0) {
                    $nu = intval($cek[0]->jum) + 1;
                } else {
                    $nu = 1;
                }

                $get = DB::connection('sqlsrvtarbak')->select("select count(id_device) as jum from users_device where id_device='$request->id_device' and nik='$request->nik'  ");
                if (count($get) > 0) {
                    if ($get[0]->jum == 0) {
                        $ins = DB::connection('sqlsrvtarbak')->insert("insert into users_device (
                            id_device,nik,nu,kode_lokasi,kode_pp,tgl_input) values('$request->id_device','$request->nik',$nu,'$kode_lokasi','$kode_pp',getdate()) ");
                    }
                } else {
                    $ins = DB::connection('sqlsrvtarbak')->insert("insert into users_device (
                        id_device,nik,nu,kode_lokasi,kode_pp,tgl_input) values('$request->id_device','$request->nik',$nu,'$kode_lokasi','$kode_pp',getdate()) ");
                }
            }
        }

        return $this->respondWithToken($token, 'siswa');
    }

    public function loginSiswa2(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $request->request->add([
            'nik2' => $request->nik
        ]);

        $credentials = $request->only(['nik2', 'password']);

        if (!$token = Auth::guard('siswa')->setTTL(10080)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if (isset($request->id_device)) {
                $kode_lokasi = Auth::guard('siswa')->user()->kode_lokasi;
                $kode_pp = Auth::guard('siswa')->user()->kode_pp;
                $nis = Auth::guard('siswa')->user()->nik;
                $cek = DB::connection('sqlsrvtarbak')->select("select count(id_device) as jum from users_device where nik='$nis'  ");
                if (count($cek) > 0) {
                    $nu = intval($cek[0]->jum) + 1;
                } else {
                    $nu = 1;
                }

                $get = DB::connection('sqlsrvtarbak')->select("select count(id_device) as jum from users_device where id_device='$request->id_device' and nik='$nis'  ");
                if (count($get) > 0) {
                    if ($get[0]->jum == 0) {
                        $ins = DB::connection('sqlsrvtarbak')->insert("insert into users_device (
                            id_device,nik,nu,kode_lokasi,kode_pp,tgl_input) values('$request->id_device','$nis',$nu,'$kode_lokasi','$kode_pp',getdate()) ");
                    }
                } else {
                    $ins = DB::connection('sqlsrvtarbak')->insert("insert into users_device (
                        id_device,nik,nu,kode_lokasi,kode_pp,tgl_input) values('$request->id_device','$nis',$nu,'$kode_lokasi','$kode_pp',getdate()) ");
                }
            }
        }

        return $this->respondWithToken($token, 'siswa');
    }

    public function loginTs(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
            'kode_pp' => 'string'
        ]);

        if (isset($request->kode_pp)) {
            $credentials = $request->only(['nik', 'password', 'kode_pp']);
        } else {
            $credentials = $request->only(['nik', 'password']);
        }

        if (!$token = Auth::guard('ts')->setTTL(10080)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if (isset($request->id_device)) {
                $kode_lokasi = Auth::guard('ts')->user()->kode_lokasi;
                $kode_pp = Auth::guard('ts')->user()->kode_pp;
                $cek = DB::connection('sqlsrvyptkug')->select("select count(id_device) as jum from users_device where nik='$request->nik'  ");
                if (count($cek) > 0) {
                    $nu = intval($cek[0]->jum) + 1;
                } else {
                    $nu = 1;
                }

                $get = DB::connection('sqlsrvyptkug')->select("select count(id_device) as jum from users_device where id_device='$request->id_device' and nik='$request->nik'  ");
                if (count($get) > 0) {
                    if ($get[0]->jum == 0) {
                        $ins = DB::connection('sqlsrvyptkug')->insert("insert into users_device (
                            id_device,nik,nu,kode_lokasi,kode_pp,tgl_input,flag_aktif) values('$request->id_device','$request->nik',$nu,'$kode_lokasi','$kode_pp',getdate(),'1') ");
                    }
                } else {
                    $ins = DB::connection('sqlsrvyptkug')->insert("insert into users_device (
                        id_device,nik,nu,kode_lokasi,kode_pp,tgl_input,flag_aktif) values('$request->id_device','$request->nik',$nu,'$kode_lokasi','$kode_pp',getdate(),'1') ");
                }
            }
        }

        return $this->respondWithToken($token, 'ts');
    }

    public function logoutTs(Request $request)
    {
        $this->validate($request, [
            'id_device' => 'required|string',
        ]);

        DB::connection('sqlsrvyptkug')->beginTransaction();
        try {

            // if(isset($request->nik) && $request->nik != ""){
            //     $ins = DB::connection('sqlsrvyptkug')->update("update users_device set flag_aktif='0' where nik='$request->nik' and id_device='$request->id_device' ");
            // }else{
            $ins = DB::connection('sqlsrvyptkug')->update("update users_device set flag_aktif='0' where id_device='$request->id_device' ");

            // }

            DB::connection('sqlsrvyptkug')->commit();
            $success['status'] = true;
            $success['message'] = "Logout berhasil";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvyptkug')->rollback();
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, 200);
        }
    }

    public function logoutSiaga(Request $request)
    {
        $this->validate($request, [
            'id_device' => 'required|string',
            'nik' => 'required|string'
        ]);

        DB::connection('dbsiaga')->beginTransaction();
        try {


            $del = DB::connection('dbsiaga')->table('users_device')
                ->where('id_device', $request->id_device)
                ->where('nik', $request->nik)
                ->delete();

            DB::connection('dbsiaga')->commit();
            $success['status'] = true;
            $success['message'] = "Logout berhasil";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('dbsiaga')->rollback();
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, 200);
        }
    }

    public function loginDago(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('dago')->setTTL(720)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'dago');
    }

    public function loginToko(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('toko')->setTTL(720)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'toko');
    }

    public function loginBangtel(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('bangtel')->setTTL(720)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'bangtel');
    }

    public function loginSiaga(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('siaga')->setTTL(720)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if (isset($request->id_device)) {
                $kode_lokasi = Auth::guard('siaga')->user()->kode_lokasi;
                $nik = Auth::guard('siaga')->user()->nik;
                $cek = DB::connection('dbsiaga')->select("select count(id_device) as jum from users_device where nik='$nik'  ");
                if (count($cek) > 0) {
                    $nu = intval($cek[0]->jum) + 1;
                } else {
                    $nu = 1;
                }

                $get = DB::connection('dbsiaga')->select("select count(id_device) as jum from users_device where id_device='$request->id_device' and nik='$nik'  ");
                if (count($get) > 0) {
                    if ($get[0]->jum == 0) {
                        $ins = DB::connection('dbsiaga')->insert("insert into users_device (
                            id_device,nik,nu,kode_lokasi,kode_pp,tgl_input) values('$request->id_device','$nik',$nu,'$kode_lokasi','-',getdate()) ");
                    }
                } else {
                    $ins = DB::connection('dbsiaga')->insert("insert into users_device (
                        id_device,nik,nu,kode_lokasi,kode_pp,tgl_input) values('$request->id_device','$nik',$nu,'$kode_lokasi','-',getdate()) ");
                }
            }
        }

        return $this->respondWithToken($token, 'siaga');
    }

    public function loginYakes(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('yakes')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'yakes');
    }

    public function loginGinas(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('ginas')->setTTL(720)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'ginas');
    }

    public function loginAdmGinas(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('admginas')->setTTL(720)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token, 'admginas');
    }

    public function simpanLog(Request $request, $id)
    {
        //validate incoming request
        if ($id == 'webginas') {
            $db = 'dbsaife';
        } else if ($id == 'webjava') {
            $db = 'dbsaife';
        }

        DB::connection($db)->beginTransaction();
        try {
            $ins = DB::connection($db)->insert("insert into lab_log ( nik,tanggal,ip,agen,kota,loc,region,negara,page,kode_lokasi,kode_pp) values ('$request->nik','$request->tanggal','$request->ip','$request->agen','$request->kota','$request->loc','$request->region','$request->negara','$request->page','$request->kode_lokasi','$request->kode_pp') ");

            DB::connection($db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Log berhasil disimpan";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection($db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Log gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function loginAdminSilo(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('silo')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if (isset($request->id_device)) {

                DB::connection('dbsilo')->table('apv_karyawan')
                    ->where('nik', $request->nik)
                    ->update(['id_device' => $request->id_device]);
            }
        }

        return $this->respondWithToken($token, 'silo');
    }
    public function loginAdminNewSilo(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['nik', 'password']);

        if (!$token = Auth::guard('newsilo')->setTTL(1440)->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if (isset($request->id_device)) {

                DB::connection('dbdev')->table('hakakses')
                    ->where('nik', $request->nik)
                    ->update(['id_device' => $request->id_device]);
            }
        }

        return $this->respondWithToken($token, 'silo');
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

        if (!$token = Auth::guard('satpam')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if ($data = Auth::guard('satpam')->user()) {
                DB::connection('sqlsrvrtrw')->beginTransaction();
                try {

                    //---------------- logout user sebelumnya
                    $get = DB::connection('sqlsrvrtrw')->select("select * from rt_satpam_log where id_satpam='$data->id_satpam' and kode_lokasi='$data->kode_lokasi' and flag_aktif='1' ");
                    if (count($get) > 0) {

                        $upd = DB::connection('sqlsrvrtrw')->table('rt_satpam_log')
                            ->where('id_satpam', $get[0]->id_satpam)
                            ->where('kode_lokasi', $get[0]->kode_lokasi)
                            ->where('flag_aktif', 1)
                            ->update(['flag_aktif' => 0, 'tgl_log_out' => date('Y-m-d H:i:s')]);
                        // Auth::guard('satpam')->invalidate($get[0]->token);
                    }

                    $ins = DB::connection('sqlsrvrtrw')->insert("insert into rt_satpam_log (id_satpam,kode_lokasi,tgl_log_in,flag_aktif,token) values ('$data->id_satpam','$data->kode_lokasi',getdate(),1,'$token') ");
                    DB::connection('sqlsrvrtrw')->commit();
                } catch (\Throwable $e) {
                    DB::connection('sqlsrvrtrw')->rollback();
                    $success['status'] = false;
                    $success['message'] = "Insert to satpam log error" . $e;
                    return response()->json($success, 200);
                }
            }
        }
        return $this->respondWithToken($token, 'satpam');
    }

    public function logoutSatpam(Request $request)
    {
        //validate incoming request
        $token = Auth::guard('satpam')->getToken();
        try {
            if ($data = Auth::guard('satpam')->user()) {
                DB::connection('sqlsrvrtrw')->beginTransaction();
                try {
                    $ins = DB::connection('sqlsrvrtrw')->update("update rt_satpam_log set tgl_log_out=getdate(), flag_aktif=0 where id_satpam='$data->id_satpam' and kode_lokasi='$data->kode_lokasi' and flag_aktif='1' ");
                    DB::connection('sqlsrvrtrw')->commit();
                    Auth::guard('satpam')->invalidate($token);
                } catch (\Throwable $e) {
                    DB::connection('sqlsrvrtrw')->rollback();
                    $success['status'] = false;
                    $success['message'] = "Update to satpam log error" . $e;
                    return response()->json($success, 200);
                }
                return response()->json([
                    'status' => true,
                    'message' => "User successfully logged out."
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

    public function loginWarga(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'no_hp' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['no_hp', 'password']);

        if (!$token = Auth::guard('warga')->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } else {
            if (isset($request->id_device)) {

                DB::connection('sqlsrvrtrw')->table('rt_warga_d')
                    ->where('no_hp', $request->no_hp)
                    ->where('pass', $request->password)
                    ->update(['id_device' => $request->id_device]);
            }
        }

        return $this->respondWithToken($token, 'warga');
    }

    public function hashPassword()
    {
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordBangtel()
    {
        $users = AdminBangtel::all();
        DB::connection('dbbangtelindo')->beginTransaction();

        try {
            DB::connection('dbbangtelindo')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('dbbangtelindo')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('dbbangtelindo')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('dbbangtelindo')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordAdmin()
    {
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordAset()
    {
        $users = AdminAset::all();
        DB::connection('dbaset')->beginTransaction();

        try {
            DB::connection('dbaset')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('dbaset')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('dbaset')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('dbaset')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordYpt()
    {
        // $users = AdminYpt::all();
        // $users = AdminYpt::where('password', NULL)->paginate(10);
        // return count($users);
        DB::connection('sqlsrvypt')->beginTransaction();

        try {
            DB::connection('sqlsrvypt')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvypt')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password', NULL)
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordRtrw()
    {
        DB::connection('sqlsrvrtrw')->beginTransaction();

        try {
            DB::connection('sqlsrvrtrw')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvrtrw')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password', NULL)
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordTarbak()
    {
        DB::connection('sqlsrvtarbak')->beginTransaction();

        try {
            DB::connection('sqlsrvtarbak')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvtarbak')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password', NULL)
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordSiswa()
    {
        DB::connection('sqlsrvtarbak')->beginTransaction();

        try {
            DB::connection('sqlsrvtarbak')->table('sis_hakakses')->where('password', NULL)->orderBy('nik')->chunk(50, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvtarbak')->table('sis_hakakses')
                        ->where('nik', $user->nik)
                        ->where('password', NULL)
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordTs()
    {
        DB::connection('sqlsrvyptkug')->beginTransaction();

        try {
            DB::connection('sqlsrvyptkug')->table('sis_hakakses')->where('password', NULL)->orderBy('nik')->chunk(50, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvyptkug')->table('sis_hakakses')
                        ->where('nik', $user->nik)
                        ->where('password', NULL)
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordYptKug()
    {
        DB::connection('sqlsrvyptkug')->beginTransaction();

        try {
            DB::connection('sqlsrvyptkug')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvyptkug')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password', NULL)
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordSju()
    {
        DB::connection('sqlsrvsju')->beginTransaction();

        try {
            DB::connection('sqlsrvsju')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvsju')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password', NULL)
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordDago()
    {
        DB::connection('sqlsrvdago')->beginTransaction();

        try {
            DB::connection('sqlsrvdago')->table('hakakses')->where('password', NULL)->orderBy('nik')->chunk(10, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvdago')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->where('password', NULL)
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordToko()
    {
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordYakes()
    {
        $users = Admin::all();
        DB::connection('dbsapkug')->beginTransaction();

        try {
            DB::connection('dbsapkug')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('dbsapkug')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('dbsapkug')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('dbsapkug')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordGinas()
    {
        $users = Admin::all();
        DB::connection('sqlsrvginas')->beginTransaction();

        try {
            DB::connection('sqlsrvginas')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvginas')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('sqlsrvginas')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvginas')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordAdmGinas()
    {
        $users = Admin::all();
        DB::connection('dbsaife')->beginTransaction();

        try {
            DB::connection('dbsaife')->table('lab_hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('dbsaife')->table('lab_hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('dbsaife')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('dbsaife')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordByNIK($db, $table, $nik)
    {
        DB::connection($db)->beginTransaction();

        try {

            $res = DB::connection($db)->select("select pass from $table where nik='$nik' ");
            $res = json_decode(json_encode($res), true);
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordCostum($db, $table, $kode_pp = null)
    {
        DB::connection($db)->beginTransaction();

        try {

            if ($kode_pp != "" or $kode_pp != NULL) {
                $filter = " and kode_pp='$kode_pp' ";
            } else {
                $filter = "";
            }

            $sql = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";

            $users = DB::connection($db)->select("SET NOCOUNT on; BEGIN tran; select nik,pass from $table where isnull(password,'-')= '-' $filter order by nik;commit tran; ");
            $i = 1;
            set_time_limit(300);
            foreach ($users as $user) {
                $sql .= " update $table set password = '" . app('hash')->make($user->pass) . "' where nik='$user->nik' and password is null ";
                if ($i % 100 == 0) {
                    $sql = $begin . $sql . $commit;
                    $ins[] = DB::connection($db)->update($sql);
                    $sql = "";
                }
                if ($i == count($users) && ($i % 100 != 0)) {
                    $sql = $begin . $sql . $commit;
                    $ins[] = DB::connection($db)->update($sql);
                    $sql = "";
                }
                $i++;
            }

            DB::connection($db)->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection($db)->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordCostumTop($db, $table, $kode_pp, $top)
    {
        DB::connection($db)->beginTransaction();

        try {

            if ($kode_pp != "" or $kode_pp != NULL) {
                $filter = " and kode_pp='$kode_pp' ";
            } else {
                $filter = "";
            }

            $sql = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";

            $users = DB::connection($db)->select("SET NOCOUNT on; BEGIN tran; select top $top nik,pass from $table where isnull(password,'-')= '-' $filter order by nik;commit tran; ");
            $i = 1;
            set_time_limit(300);
            foreach ($users as $user) {
                $sql .= " update $table set password = '" . app('hash')->make($user->pass) . "' where nik='$user->nik' and password is null ";
                if ($i % 500 == 0) {
                    $sql = $begin . $sql . $commit;
                    $ins[] = DB::connection($db)->update($sql);
                    $sql = "";
                }
                if ($i == count($users) && ($i % 500 != 0)) {
                    $sql = $begin . $sql . $commit;
                    $ins[] = DB::connection($db)->update($sql);
                    $sql = "";
                }
                $i++;
            }

            DB::connection($db)->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection($db)->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordSiaga()
    {
        $db = "dbsiaga";
        $table = "hakakses";
        DB::connection($db)->beginTransaction();

        try {

            $sql = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";

            $users = DB::connection($db)->select("SET NOCOUNT on; BEGIN tran; select nik,pass from $table where isnull(password,'-')= '-' order by nik;commit tran; ");
            $i = 1;
            set_time_limit(300);
            foreach ($users as $user) {
                $sql .= " update $table set password = '" . app('hash')->make($user->pass) . "' where nik='$user->nik' and password is null ";
                if ($i % 1000 == 0) {
                    $sql = $begin . $sql . $commit;
                    $ins[] = DB::connection($db)->update($sql);
                    $sql = "";
                }
                if ($i == count($users) && ($i % 1000 != 0)) {
                    $sql = $begin . $sql . $commit;
                    $ins[] = DB::connection($db)->update($sql);
                    $sql = "";
                }
                $i++;
            }

            DB::connection($db)->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection($db)->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordCostum2($db, $table, $top, $kode_pp)
    {
        DB::connection($db)->beginTransaction();

        try {

            if ($kode_pp != "" or $kode_pp != NULL) {
                $filter = " and kode_pp='$kode_pp' ";
            } else {
                $filter = "";
            }

            $users = DB::connection($db)->select("select top $top nik,pass from $table where status_login= 'S' and isnull(password,'-')= '-' $filter order by nik ");

            foreach ($users as $user) {
                DB::connection($db)->table($table)
                    ->where('nik', $user->nik)
                    ->where('password', NULL)
                    ->update(['password' => app('hash')->make($user->pass)]);
            }

            DB::connection($db)->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection($db)->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPassTable($db, $table)
    {
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPassWarga(Request $request)
    {
        $users = AdminWarga::all();
        DB::connection('sqlsrvrtrw')->beginTransaction();

        try {
            DB::connection('sqlsrvrtrw')->table('rt_warga_d')->orderBy('no_rumah')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('sqlsrvrtrw')->table('rt_warga_d')
                        ->where('no_bukti', $user->no_bukti)
                        ->where('no_urut', $user->no_urut)
                        ->where('kode_pp', $user->kode_pp)
                        ->where('kode_lokasi', $user->kode_lokasi)
                        ->where('no_rumah', $user->no_rumah)
                        ->where('pass', '<>', ' ')
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
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPassDago(Request $request)
    {
        $users = Admin::all();
        DB::connection('sqlsrv2')->beginTransaction();

        try {
            DB::connection('sqlsrv2')
                ->table('hakakses')
                ->where('kode_lokasi', '11')
                ->where('kode_klp_menu', 'LIKE', 'DAGO%')
                ->orderBy('nik')->chunk(100, function ($users) {
                    foreach ($users as $user) {
                        DB::connection('sqlsrv2')->table('hakakses')
                            ->where('kode_lokasi', '11')
                            ->where('kode_klp_menu', 'LIKE', 'DAGO%')
                            ->update(['password' => app('hash')->make($user->pass)]);
                    }
                });
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordAdminSilo()
    {
        $users = AdminSilo::all();
        DB::connection('dbsilo')->beginTransaction();

        try {
            DB::connection('dbsilo')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('dbsilo')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('dbsilo')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('dbsilo')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }

    public function hashPasswordAdminNewSilo()
    {
        $users = AdminSilo::all();
        DB::connection('dbdev')->beginTransaction();

        try {
            DB::connection('dbdev')->table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::connection('dbdev')->table('hakakses')
                        ->where('nik', $user->nik)
                        ->update(['password' => app('hash')->make($user->pass)]);
                }
            });
            DB::connection('dbdev')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('dbdev')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan " . $e;
            return response()->json($success, 200);
        }
    }
}
