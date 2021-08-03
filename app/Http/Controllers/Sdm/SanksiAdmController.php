<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class SanksiAdmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getNU($nik, $kode_lokasi) {
        $select= "SELECT max(nu) AS nu FROM hr_sanksi WHERE nik='".$nik."' AND kode_lokasi='".$kode_lokasi."'";
        $result = DB::connection($this->db)->select($select);
        $nu = NULL;

        if(count($result) > 0){
            $nu = $result[0]->nu + 1;
        } else {
            $nu = 1;
        }

        return $nu;
    }

    public function isUnik($isi, $kode_lokasi){
        
        $auth = DB::connection($this->db)->select("SELECT nik FROM hr_sanksi WHERE nik ='".$isi."' AND kode_lokasi = '".$kode_lokasi."'");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT a.nik, b.nama, count(a.nik) as jumlah 
            FROM hr_sanksi a 
            INNER JOIN hr_karyawan b ON a.nik=b.nik AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."'
            GROUP BY a.nik, b.nama";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $karyawan = "SELECT nik, nama FROM hr_karyawan WHERE nik = '".$request->query('nik')."' 
            AND kode_lokasi = '".$kode_lokasi."'";
            $resKaryawan = DB::connection($this->db)->select($karyawan);
            $resKaryawan = json_decode(json_encode($resKaryawan),true);

            $sql = "SELECT nik, nama, convert(varchar,tanggal,103) as tanggal, jenis   
            FROM hr_sanksi
            WHERE nik = '".$request->query('nik')."' AND kode_lokasi = '".$kode_lokasi."'";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $resKaryawan;
                $success['detail'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
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
            'nik' => 'required',
            'nama' => 'required|array',
            'tanggal' => 'required|array',
            'jenis' => 'required|array'
        ]);
        
        DB::connection($this->db)->beginTransaction();

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->input('nik'), $kode_lokasi)) { 
                for($i=0;$i<count($request->input('nomor'));$i++) { 
                    $nu = $this->getNU($request->input('nik'),$kode_lokasi);
                    $nama = $request->input('nama');
                    $tanggal = $request->input('tanggal');
                    $jenis = $request->input('jenis');

                    $insert = "INSERT INTO hr_sanksi(nik, kode_lokasi, nama, tanggal, jenis, nu) 
                    VALUES ('".$request->input('nik')."', '".$kode_lokasi."', '".$nama[$i]."', 
                    '".$tanggal[$i]."', '".$jenis[$i]."', '".$nu."')";

                    DB::connection($this->db)->insert($insert);
                }

                DB::connection($this->db)->commit();
                $success['kode'] = $request->input('nik');
                $success['status'] = true;
                $success['message'] = "Data sanksi karyawan berhasil disimpan";
            } else {
                $success['kode'] = $request->input('nik');
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK SK karyawan sudah ada di database!";
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data sanksi karyawan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				   
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required',
            'nama' => 'required|array',
            'tanggal' => 'required|array',
            'jenis' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->db)->table('hr_sk')
            ->where('nik', $request->nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            for($i=0;$i<count($request->input('nomor'));$i++) { 
                $nu = $this->getNU($request->input('nik'),$kode_lokasi);
                $nama = $request->input('nama');
                $tanggal = $request->input('tanggal');
                $jenis = $request->input('jenis');

                $insert = "INSERT INTO hr_sanksi(nik, kode_lokasi, nama, tanggal, jenis, nu) 
                VALUES ('".$request->input('nik')."', '".$kode_lokasi."', '".$nama[$i]."', 
                '".$tanggal[$i]."', '".$jenis[$i]."', '".$nu."')";

                DB::connection($this->db)->insert($insert);
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data sanksi karyawan berhasil diubah";
            $success['kode'] = $request->input('nik');
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data sanksi karyawan gagal diubah ".$e;
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
            'nik' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->table('hr_sanksi')
            ->where('nik', $request->nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            $success['status'] = true;
            $success['message'] = "Data sanksi karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data sanksi karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
