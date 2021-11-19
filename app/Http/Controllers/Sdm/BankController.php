<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BankController extends Controller
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

        $auth = DB::connection($this->db)->select("SELECT kode_bank FROM hr_bank WHERE kode_bank ='" . $isi . "' AND kode_lokasi = '" . $kode_lokasi . "'");
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

            $sql = "SELECT kode_bank,kode_lokasi,nama  FROM hr_bank WHERE kode_lokasi = '" . $kode_lokasi . "' ";
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
            'kode_bank' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT kode_bank, nama, kode_lokasi FROM hr_bank WHERE kode_bank = '" . $request->kode_bank . "' AND kode_lokasi = '" . $kode_lokasi . "'";
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
            'kode_bank' => 'required',
            'nama' => 'required',
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            if ($this->isUnik($request->input('kode_bank'), $kode_lokasi)) {
                $insert = "INSERT INTO hr_bank(kode_bank, nama,kode_lokasi,nik_user)
                VALUES (?, ?, ?, ?)";

                DB::connection($this->db)->insert($insert, [
                    $request->input('kode_bank'),
                    $request->input('nama'),
                    $kode_lokasi,
                    $nik
                ]);

                $success['status'] = true;
                $success['message'] = "Data Bank  berhasil disimpan";
            } else {
                $success['status'] = false;
                $success['message'] = "Kode yang dimasukan sudah digunakan, gunakan kode lainnya.!";
            }
            $success['kode'] = $request->kode_bank;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Bank  gagal disimpan " . $e;
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
            'kode_bank' => 'required',
            'nama' => 'required',
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $update = "UPDATE hr_bank SET nama = '" . $request->input('nama') . "'
            WHERE kode_bank = '" . $request->input('kode_bank') . "'
            AND kode_lokasi = '" . $kode_lokasi . "'";

            DB::connection($this->db)->update($update);

            $success['status'] = true;
            $success['message'] = "Data Bank  berhasil diubah";
            $success['kode'] = $request->kode_bank;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Bank  gagal diubah " . $e;
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
            'kode_bank' => 'required'
        ]);

        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_bank')
                ->where('kode_bank', $request->kode_bank)
                ->where('kode_lokasi', $kode_lokasi)
                ->delete();

            $success['status'] = true;
            $success['message'] = "Data Bank  berhasil dihapus";

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Bank  gagal dihapus " . $e;

            return response()->json($success, $this->successStatus);
        }
    }
}
