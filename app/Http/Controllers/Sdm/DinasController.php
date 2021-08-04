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

            $sql = "SELECT no_sk, nama, nu, convert(varchar,tgl_sk,103) as tgl_sk 
            FROM hr_sk WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."' ";
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
            'nu' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT nama, nu, no_sk, convert(varchar,tgl_sk,103) as tgl_sk   
            FROM hr_sk
            WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."' AND nu = '".$request->nu."'";
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
            'nama' => 'required',
            'no_sk' => 'required',
            'tgl_sk' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $nu = $this->getNU($nik,$kode_lokasi);

            $insert = "INSERT INTO hr_sk(nik, kode_lokasi, no_sk, nama, tgl_sk, nu) 
            VALUES ('".$nik."', '".$kode_lokasi."', '".$request->input('no_sk')."', 
            '".$request->input('nama')."', '".$request->input('tgl_sk')."', '".$nu."')";

            DB::connection($this->db)->insert($insert);

            $success['kode'] = $request->no_sk;
            $success['status'] = true;
            $success['message'] = "Data SK karyawan berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data SK karyawan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				   
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'nama' => 'required',
            'no_sk' => 'required',
            'tgl_sk' => 'required',
            'nu' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $update = "UPDATE hr_sk SET no_sk = '".$request->input('no_sk')."', nama = '".$request->input('nama')."',
            tgl_sk = '".$request->input('tgl_sk')."'
            WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."' AND nu = '".$request->input('nu')."'";
            
            DB::connection($this->db)->update($update);

            $success['status'] = true;
            $success['message'] = "Data SK karyawan berhasil diubah";
            $success['kode'] = $request->no_sk;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
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
            'nu' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->table('hr_sk')
            ->where('nik', $nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nu', $request->nu)
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
