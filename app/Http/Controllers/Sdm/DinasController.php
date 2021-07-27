<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class DinasController extends Controller {

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

    public function index(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT no_sk, nama FROM hr_sk WHERE kode_lokasi = '".$kode_lokasi."' ";
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

            $sql = "SELECT nik, nama, nu, no_sk, tgl_sk   
            FROM hr_sk
            WHERE nik = '".$request->nik."' AND kode_lokasi = '".$kode_lokasi."'";
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
            'nomor' => 'required|array',
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
                    $nik = $request->input('nik');
                    $nama = $request->input('nama');
                    $nomor = $request->input('nomor');
                    $sk = $request->input('no_sk');
                    $tgl_sk = $request->input('tgl_sk');
                    

                    $insert = "INSERT INTO hr_sk(nik, kode_lokasi, nu, no_sk, nama, tgl_sk) 
                    VALUES ('".$nik."', '".$kode_lokasi."', '".$nomor[$i]."', '".$sk[$i]."', '".$nama[$i]."',
                    '".$tgl_sk[$i]."')";

                    DB::connection($this->db)->insert($insert);   
                }
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data SK karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK SK karyawan sudah ada di database!";
            }
            $success['kode'] = $request->nik;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data SK karyawan gagal disimpan ".$e;
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
            'nik' => 'required',
            'nama' => 'required|array',
            'nomor' => 'required|array',
            'no_sk' => 'required|array',
            'tgl_sk' => 'required|array'
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


            for($i=0;$i<count($request->input('nomor'));$i++) {
                $nik = $request->input('nik');
                $nama = $request->input('nama');
                $nomor = $request->input('nomor');
                $sk = $request->input('no_sk');
                $tgl_sk = $request->input('tgl_sk');
                
                $insert = "INSERT INTO hr_sk(nik, kode_lokasi, nu, no_sk, nama, tgl_sk) 
                VALUES ('".$nik."', '".$kode_lokasi."', '".$nomor[$i]."', '".$sk[$i]."', '".$nama[$i]."',
                '".$tgl_sk[$i]."')";

                DB::connection($this->db)->insert($insert);   
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data SK karyawan berhasil diubah";
            $success['kode'] = $request->nik;
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
