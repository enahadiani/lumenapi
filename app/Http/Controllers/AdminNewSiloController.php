<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use  App\Admin;

class AdminNewSiloController extends Controller
{
    public $db = "dbnewsilo";
    public $guard = "newsilo";
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware();
    // }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    public function profile()
    {
        if ($data =  Auth::guard($this->guard)->user()) {
            $nik = $data->nik;
            $kode_lokasi = $data->kode_lokasi;

            $user = DB::connection($this->db)->select("select a.kode_klp_menu, a.nik, a.nama, a.status_admin, a.klp_akses, a.kode_lokasi,b.nama as nmlok, c.kode_pp,d.nama as nama_pp,
			b.kode_lokkonsol,d.kode_bidang, c.foto,isnull(e.form,'-') as path_view,b.logo,c.no_telp,c.jabatan
            from hakakses a
            inner join lokasi b on b.kode_lokasi = a.kode_lokasi
            left join karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi
            left join pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi
            left join m_form e on a.path_view=e.kode_form
            where a.nik= '$nik'
            ");
            $user = json_decode(json_encode($user), true);

            if (count($user) > 0) { //mengecek apakah data kosong atau tidak
                $periode = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                ");
                $periode = json_decode(json_encode($periode), true);

                $fs = DB::connection($this->db)->select("select kode_fs from fs where kode_lokasi='$kode_lokasi'
                ");
                $fs = json_decode(json_encode($fs), true);

                return response()->json(['user' => $user, 'periode' => $periode, 'kode_fs' => $fs], 200);
            } else {
                return response()->json(['user' => [], 'periode' => [], 'kode_fs' => []], 200);
            }
        } else {
            return response()->json(['user' => [], 'periode' => [], 'kode_fs' => []], 200);
        }
    }

    public function profile2()
    {
        if ($data =  Auth::guard($this->guard)->user()) {
            $nik = $data->nik;
            $kode_lokasi = $data->kode_lokasi;

            $user = DB::connection($this->db)->select("select a.kode_klp_menu, a.nik, a.nama, a.status_admin, a.klp_akses, a.kode_lokasi,b.nama as nmlok, c.kode_pp,d.nama as nama_pp,
            c.foto,isnull(e.form,'-') as path_view,b.logo,c.no_telp,c.jabatan,c.email
                  from hakakses a
                  inner join lokasi b on b.kode_lokasi = a.kode_lokasi
                  left join apv_karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi
                  left join apv_pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi
                  left join m_form e on a.path_view=e.kode_form
                  where a.nik='$nik'
            ");
            $user = json_decode(json_encode($user), true);

            if (count($user) > 0) { //mengecek apakah data kosong atau tidak
                $periode = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                ");
                $periode = json_decode(json_encode($periode), true);

                $fs = DB::connection($this->db)->select("select kode_fs from fs where kode_lokasi='$kode_lokasi'
                ");
                $fs = json_decode(json_encode($fs), true);

                return response()->json(['user' => $user, 'periode' => $periode, 'kode_fs' => $fs], 200);
            } else {
                return response()->json(['user' => [], 'periode' => [], 'kode_fs' => []], 200);
            }
        } else {
            return response()->json(['user' => [], 'periode' => [], 'kode_fs' => []], 200);
        }
    }

    public function profileMobileApv()
    {
        if ($data =  Auth::guard($this->guard)->user()) {
            $nik = $data->nik;
            $kode_lokasi = $data->kode_lokasi;


            $url = url('api/apv/storage');
            $user = DB::connection($this->db)->select("select a.nik, c.nama,
            case when foto != '-' then '" . $url . "/'+foto else '-' end as foto,b.logo,c.no_telp,c.kode_jab,c.email,f.nama as jabatan,c.id_device
                  from hakakses a
                  inner join lokasi b on b.kode_lokasi = a.kode_lokasi
                  left join apv_karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi
                  left join apv_pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi
                  left join apv_jab f on c.kode_jab=f.kode_jab and c.kode_lokasi=f.kode_lokasi
                  left join m_form e on a.path_view=e.kode_form
                  where a.nik='$nik'
            ");
            $user = json_decode(json_encode($user), true);

            if (count($user) > 0) { //mengecek apakah data kosong atau tidak
                $periode = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                ");
                $periode = json_decode(json_encode($periode), true);

                $fs = DB::connection($this->db)->select("select kode_fs from fs where kode_lokasi='$kode_lokasi'
                ");
                $fs = json_decode(json_encode($fs), true);

                return response()->json(['user' => $user, 'periode' => $periode, 'kode_fs' => $fs], 200);
            } else {
                return response()->json(['user' => [], 'periode' => [], 'kode_fs' => []], 200);
            }
        } else {
            return response()->json(['user' => [], 'periode' => [], 'kode_fs' => []], 200);
        }
    }

    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'password_lama' => 'required',
            'password_baru' => 'required'
        ]);
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            DB::connection($this->db)->beginTransaction();

            $upd =  DB::connection($this->db)->table('hakakses')
                ->where('nik', $nik)
                ->where('pass', $request->password_lama)
                ->update(['pass' => $request->password_baru, 'password' => app('hash')->make($request->password_baru)]);

            if ($upd) { //mengecek apakah data kosong atau tidak
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Password berhasil diubah";
                return response()->json($success, 200);
            } else {
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = "Password gagal diubah";
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {

            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, 200);
        }
    }

    /**
     * Get all User.
     *
     * @return Response
     */
    public function allUsers()
    {
        return response()->json(['users' =>  Admin::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = Admin::findOrFail($id);

            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }
    }

    public function cekPayload()
    {
        $payload = Auth::guard($this->guard)->payload();
        // $payload->toArray();
        return response()->json(['payload' => $payload], 200);
    }
}
