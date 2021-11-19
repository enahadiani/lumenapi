<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KlienController extends Controller
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

        $auth = DB::connection($this->db)->select("SELECT kode_client FROM hr_client WHERE kode_client ='" . $isi . "'");
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

            $sql = "SELECT kode_client,nama_client as nama, flag_aktif as is_active   FROM hr_client";
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
            'kode_client' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT kode_client, nama_client as nama, flag_aktif as is_active FROM hr_client WHERE kode_client = '" . $request->kode_client . "' ";
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
            'kode_client' => 'required',
            'nama_client' => 'required',
            'flag_aktif' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            if ($this->isUnik($request->input('kode_client'), $kode_lokasi)) {
                $insert = "INSERT INTO hr_client(kode_client, nama_client, flag_aktif)
                VALUES (?, ?, ?)";

                DB::connection($this->db)->insert($insert, [
                    $request->input('kode_client'),
                    $request->input('nama_client'),
                    $request->input('flag_aktif')
                ]);

                $success['status'] = true;
                $success['message'] = "Data klien berhasil disimpan";
            } else {
                $success['status'] = false;
                $success['message'] = "Kode yang dimasukan sudah digunakan, gunakan kode lainnya.!";
            }
            $success['kode'] = $request->kode_client;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data klien gagal disimpan " . $e;
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
            'kode_client' => 'required',
            'nama_client' => 'required',
            'flag_aktif' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $update = "UPDATE hr_client SET nama_client = '" . $request->input('nama_client') . "', flag_aktif = '" . $request->input("flag_aktif") . "'
            WHERE kode_client = '" . $request->input('kode_client') . "'";

            DB::connection($this->db)->update($update);

            $success['status'] = true;
            $success['message'] = "Data klien berhasil diubah";
            $success['kode'] = $request->kode_client;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data klien gagal diubah " . $e;
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
            'kode_client' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_client')
                ->where('kode_client', $request->kode_client)
                ->delete();

            $success['status'] = true;
            $success['message'] = "Data klien berhasil dihapus";

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data klien gagal dihapus " . $e;

            return response()->json($success, $this->successStatus);
        }
    }
}
