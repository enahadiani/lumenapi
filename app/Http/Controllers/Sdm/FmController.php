<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function isUnik($isi, $kode_lokasi)
    {

        $auth = DB::connection($this->db)->select("SELECT kode_fm FROM hr_fm WHERE kode_fm ='" . $isi . "' AND kode_lokasi = '" . $kode_lokasi . "'");
        $auth = json_decode(json_encode($auth), true);
        if (count($auth) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT a.kode_fm,a.kode_lokasi,a.nama,concat(a.kode_area,' - ',b.nama) as area
            FROM hr_fm  a
            INNER JOIN hr_area  b
            ON a.kode_area=b.kode_area AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '" . $kode_lokasi . "' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) {
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);
            } else {

                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";

                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'kode_fm' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT a.kode_fm,a.kode_lokasi,a.nama,a.kode_area,b.nama as nama_area
            FROM hr_fm  a
            INNER JOIN hr_area  b
            ON a.kode_area=b.kode_area AND a.kode_lokasi=b.kode_lokasi
             WHERE a.kode_fm = '" . $request->kode_fm . "' AND a.kode_lokasi = '" . $kode_lokasi . "'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) {
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);
            } else {

                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";

                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    // filter berdasarkan kode area
    public function filterArea(Request $request)
    {
        $this->validate($request, [
            'kode_area' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT a.kode_fm,a.kode_lokasi,a.nama,a.kode_area,b.nama as nama_area
            FROM hr_fm  a
            INNER JOIN hr_area  b
            ON a.kode_area=b.kode_area AND a.kode_lokasi=b.kode_lokasi
             WHERE a.kode_area = '" . $request->kode_area . "' AND a.kode_lokasi = '" . $kode_lokasi . "'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) {
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);
            } else {

                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";

                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_fm' => 'required',
            'nama' => 'required',
            'kode_area' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            if ($this->isUnik($request->input('kode_fm'), $kode_lokasi)) {
                $insert = "INSERT INTO hr_fm(kode_fm, nama,kode_area ,kode_lokasi,nik_user)
                VALUES (?, ?, ?, ?,?)";

                DB::connection($this->db)->insert($insert, [
                    $request->input('kode_fm'),
                    $request->input('nama'),
                    $request->input('kode_area'),
                    $kode_lokasi,
                    $nik
                ]);

                $success['status'] = true;
                $success['message'] = "Data FM karyawan berhasil disimpan";
            } else {
                $success['status'] = false;
                $success['message'] = "Kode yang dimasukan sudah digunakan, gunakan kode lainnya.!";
            }
            $success['kode'] = $request->kode_fm;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data FM karyawan gagal disimpan " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_fm' => 'required',
            'nama' => 'required',
            'kode_area' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $update = "UPDATE hr_fm SET nama = '" . $request->input('nama') . "',kode_area = '" . $request->input('kode_area') . "'
            WHERE kode_fm = '" . $request->input('kode_fm') . "'
            AND kode_lokasi = '" . $kode_lokasi . "'";

            DB::connection($this->db)->update($update);

            $success['status'] = true;
            $success['message'] = "Data FM karyawan berhasil diubah";
            $success['kode'] = $request->kode_fm;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data FM karyawan gagal diubah " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_fm' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_fm')
                ->where('kode_fm', $request->kode_fm)
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

            $success['status'] = true;
            $success['message'] = "Data FM karyawan berhasil dihapus";

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data FM karyawan gagal dihapus " . $e;

            return response()->json($success, $this->successStatus);
        }
    }
}
