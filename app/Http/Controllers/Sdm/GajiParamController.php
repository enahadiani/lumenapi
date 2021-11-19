<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GajiParamController extends Controller
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

        $auth = DB::connection($this->db)->select("SELECT kode_param FROM hr_gaji_param WHERE kode_param ='" . $isi . "' AND kode_lokasi = '" . $kode_lokasi . "'");
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

            $sql = "SELECT kode_param,kode_lokasi,nama  FROM hr_gaji_param WHERE kode_lokasi = '" . $kode_lokasi . "' ";
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
            'kode_param' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT kode_param, nama, kode_lokasi FROM hr_gaji_param WHERE kode_param = '" . $request->kode_param . "' AND kode_lokasi = '" . $kode_lokasi . "'";
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
            'kode_param' => 'required',
            'nama' => 'required',
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            if ($this->isUnik($request->input('kode_param'), $kode_lokasi)) {
                $insert = "INSERT INTO hr_gaji_param(kode_param, nama, kode_lokasi)
                VALUES (?, ?, ?)";

                DB::connection($this->db)->insert($insert, [
                    $request->input('kode_param'),
                    $request->input('nama'),
                    $kode_lokasi
                ]);

                $success['status'] = true;
                $success['message'] = "Data parameter gaji karyawan berhasil disimpan";
            } else {
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode param karyawan sudah ada di database!";
            }
            $success['kode'] = $request->kode_param;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data parameter gaji karyawan gagal disimpan " . $e;
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
            'kode_param' => 'required',
            'nama' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $update = "UPDATE hr_gaji_param SET nama = '" . $request->input('nama') . "'
            WHERE kode_param = '" . $request->input('kode_param') . "'
            AND kode_lokasi = '" . $kode_lokasi . "'";

            DB::connection($this->db)->update($update);

            $success['status'] = true;
            $success['message'] = "Data parameter gaji karyawan berhasil diubah";
            $success['kode'] = $request->kode_param;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data parameter gaji karyawan gagal diubah " . $e;
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
            'kode_param' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_gaji_param')
                ->where('kode_param', $request->kode_param)
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

            $success['status'] = true;
            $success['message'] = "Data parameter karyawan berhasil dihapus";

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data parameter karyawan gagal dihapus " . $e;

            return response()->json($success, $this->successStatus);
        }
    }
}
