<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class GolonganController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function isUnik($isi, $kode_lokasi){
        
        $auth = DB::connection($this->db)->select("SELECT kode_gol FROM hr_gol WHERE kode_gol ='".$isi."' AND kode_lokasi = '".$kode_lokasi."'");
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

            $sql = "SELECT kode_gol, nama, flag_aktif FROM hr_gol WHERE kode_lokasi = '".$kode_lokasi."' ";
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
            'kode_gol' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT kode_gol, nama, flag_aktif FROM hr_gol WHERE kode_gol = '".$request->kode_gol."' AND kode_lokasi = '".$kode_lokasi."'";
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
            'kode_gol' => 'required',
            'nama' => 'required',
            'status' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->input('kode_gol'), $kode_lokasi)){
                $insert = "INSERT INTO hr_gol(kode_gol, nama, flag_aktif, kode_lokasi) 
                VALUES (?, ?, ?, ?)";

                DB::connection($this->db)->insert($insert, [
                    $request->input('kode_gol'),
                    $request->input('nama'),
                    $request->input('status'),
                    $kode_lokasi
                ]);
                
                $success['status'] = true;
                $success['message'] = "Data golongan karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode golongan karyawan sudah ada di database!";
            }
            $success['kode'] = $request->kode_gol;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data golongan karyawan gagal disimpan ".$e;
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
            'kode_gol' => 'required',
            'nama' => 'required',
            'status' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $update = "UPDATE hr_gol SET nama = '".$request->input('nama')."', 
            flag_aktif = '".$request->input('status')."'
            WHERE kode_gol = '".$request->input('kode_gol')."' AND kode_lokasi = '".$kode_lokasi."'";
            
            DB::connection($this->db)->update($update);
            
            $success['status'] = true;
            $success['message'] = "Data golongan karyawan berhasil diubah";
            $success['kode'] = $request->kode_gol;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data golongan karyawan gagal diubah ".$e;
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
            'kode_gol' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->table('hr_gol')
            ->where('kode_gol', $request->kode_gol)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            $success['status'] = true;
            $success['message'] = "Data golongan karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data golongan karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
