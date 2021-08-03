<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class DinasAdmController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function isUnik($isi, $kode_lokasi){
        
        $auth = DB::connection($this->db)->select("SELECT nik FROM hr_sk WHERE nik ='".$isi."' AND kode_lokasi = '".$kode_lokasi."'");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }
    
    public function getNU($nik, $kode_lokasi) {
        $select= "SELECT max(nu) AS nu FROM hr_sk WHERE nik='".$nik."' AND kode_lokasi='".$kode_lokasi."'";
        $result = DB::connection($this->db)->select($select);
        $nu = NULL;

        if(count($result) > 0){
            $nu = $result[0]->nu + 1;
        } else {
            $nu = 1;
        }

        return $nu;
    }

    public function index(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT a.nik, a.nama, isnull(b.jum,0) AS jum
            FROM hr_karyawan a
            LEFT JOIN (select a.nik,a.kode_lokasi,count(*) AS jum
			FROM hr_sk a
		    GROUP BY a.nik,a.kode_lokasi
			) b ON a.nik=b.nik AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."'";
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

            $sql = "SELECT nama, no_sk, tgl_sk   
            FROM hr_sk
            WHERE nik = '".$request->query('nik')."' AND kode_lokasi = '".$kode_lokasi."'";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($resKaryawan) > 0){ 
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
            'no_sk' => 'required|array',
            'tgl_sk' => 'required|array'
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
                    $sk = $request->input('no_sk');
                    $nama = $request->input('nama');
                    $tgl_sk = $request->input('tgl_sk');

                    $insert = "INSERT INTO hr_sk(nik, kode_lokasi, no_sk, nama, tgl_sk, nu) 
                    VALUES ('".$request->input('nik')."', '".$kode_lokasi."', '".$sk[$i]."', 
                    '".$nama[$i]."', '".$tgl_sk[$i]."', '".$nu."')";

                    DB::connection($this->db)->insert($insert);
                }

                DB::connection($this->db)->commit();
                $success['kode'] = $request->input('nik');
                $success['status'] = true;
                $success['message'] = "Data SK karyawan berhasil disimpan";
            } else {
                $success['kode'] = $request->input('nik');
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK SK karyawan sudah ada di database!";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data SK karyawan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				   
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required',
            'nama' => 'required|array',
            'no_sk' => 'required|array',
            'tgl_sk' => 'required|array'
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
                $sk = $request->input('no_sk');
                $nama = $request->input('nama');
                $tgl_sk = $request->input('tgl_sk');

                $insert = "INSERT INTO hr_sk(nik, kode_lokasi, no_sk, nama, tgl_sk, nu) 
                VALUES ('".$request->input('nik')."', '".$kode_lokasi."', '".$sk[$i]."', 
                '".$nama[$i]."', '".$tgl_sk[$i]."', '".$nu."')";

                DB::connection($this->db)->insert($insert);
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data SK karyawan berhasil diubah";
            $success['kode'] = $request->input('nik');
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data SK karyawan gagal diubah ".$e;
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
            
            DB::connection($this->db)->table('hr_sk')
            ->where('nik', $request->nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            $success['status'] = true;
            $success['message'] = "Data SK karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data SK karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
