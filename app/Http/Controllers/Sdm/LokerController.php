<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LokerController extends Controller
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

        $auth = DB::connection($this->db)->select("SELECT kode_loker FROM hr_loker WHERE kode_loker ='" . $isi . "' AND kode_lokasi = '" . $kode_lokasi . "'");
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

            $sql = "SELECT kode_loker, nama, flag_aktif FROM hr_loker WHERE kode_lokasi = '" . $kode_lokasi . "' ";
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
            'kode_loker' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT a.kode_loker,a.nama,a.flag_aktif,a.kode_area,
            b.nama AS nama_area, a.kode_fm,c.nama as nama_fm,a.kode_bm, d.nama as nama_bm
            FROM hr_loker a
            INNER JOIN hr_area b ON a.kode_area=b.kode_area AND a.kode_lokasi=b.kode_lokasi
            INNER JOIN hr_fm c ON a.kode_fm=c.kode_fm AND a.kode_lokasi=c.kode_lokasi
            INNER JOIN hr_bm d ON a.kode_bm=d.kode_bm AND a.kode_lokasi=d.kode_lokasi WHERE a.kode_loker = '" . $request->kode_loker . "' AND a.kode_lokasi = '" . $kode_lokasi . "'";
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
    public function save(Request $request)
    {
        $this->validate($request, [
            'kode_loker' => 'required',
            'nama' => 'required',
            'status' => 'required',
            'kode_area' => 'required',
            'kode_fm' => 'required',
            'kode_bm' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            if ($this->isUnik($request->input('kode_loker'), $kode_lokasi)) {
                $insert = "INSERT INTO hr_loker(kode_loker, nama, flag_aktif, kode_lokasi,kode_area,kode_fm,kode_bm)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

                DB::connection($this->db)->insert($insert, [
                    $request->input('kode_loker'),
                    $request->input('nama'),
                    $request->input('status'),
                    $kode_lokasi,
                    $request->input('kode_area'),
                    $request->input('kode_fm'),
                    $request->input('kode_bm'),
                ]);

                $success['status'] = true;
                $success['message'] = "Data lokasi kerja berhasil disimpan";
            } else {
                $success['status'] = false;
                $success['message'] = "Kode yang dimasukan sudah digunakan, gunakan kode lainnya.!";
            }
            $success['kode'] = $request->kode_loker;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data lokasi kerja gagal disimpan " . $e;
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
            'kode_loker' => 'required',
            'nama' => 'required',
            'status' => 'required',
            'kode_area' => 'required',
            'kode_fm' => 'required',
            'kode_bm' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $update = "UPDATE hr_loker SET nama = '" . $request->input('nama') . "',
            flag_aktif = '" . $request->input('status') . "',
            kode_area = '" . $request->input('kode_area') . "',
            kode_fm = '" . $request->input('kode_fm') . "',
            kode_bm = '" . $request->input('kode_bm') . "'
            WHERE kode_loker = '" . $request->input('kode_loker') . "' AND kode_lokasi = '" . $kode_lokasi . "'";

            DB::connection($this->db)->update($update);

            $success['status'] = true;
            $success['message'] = "Data lokasi kerja berhasil diubah";
            $success['kode'] = $request->kode_loker;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data lokasi kerja gagal diubah " . $e;
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
            'kode_loker' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_loker')
                ->where('kode_loker', $request->kode_loker)
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

            $success['status'] = true;
            $success['message'] = "Data lokasi kerja berhasil dihapus";

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data lokasi kerja gagal dihapus " . $e;

            return response()->json($success, $this->successStatus);
        }
    }
}
