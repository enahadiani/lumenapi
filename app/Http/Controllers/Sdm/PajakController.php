<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PajakController extends Controller
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
        
        $auth = DB::connection($this->db)->select("select kode_pajak from hr_pajak where kode_pajak ='".$isi."' and kode_lokasi = '".$kode_lokasi."'");
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

            $sql = "SELECT kode_pajak, nama, nilai from hr_pajak where kode_lokasi = '".$kode_lokasi."' ";
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
            'kode_pajak' => 'required',
            'nama' => 'required',
            'nilai' => 'required',
            'biaya_jab' => 'required',
            'jab_max' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->input('kode_pajak'), $kode_lokasi)){
                $insert = "INSERT INTO hr_pajak(kode_pajak, nama, nilai, biaya_jab, jab_max, kode_lokasi) 
                VALUES ('".$request->input('kode_pajak')."', '".$request->input('nama')."', 
                '".$request->input('nilai')."', '".$request->input('biaya_jab')."', '".$request->input('jab_max')."',
                '".$kode_lokasi."')";

                DB::connection($this->db)->insert($insert);
                
                $success['status'] = true;
                $success['message'] = "Data pajak karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode pajak karyawan sudah ada di database!";
            }
            $success['kode'] = $request->kode_pajak;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data pajak karyawan gagal disimpan ".$e;
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
            'kode_pajak' => 'required',
            'nama' => 'required',
            'nilai' => 'required',
            'biaya_jab' => 'required',
            'jab_max' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $update = "UPDATE hr_pajak SET nama = '".$request->input('nama')."', 
            nilai = '".$request->input('nilai')."', biaya_jab = '".$request->input('biaya_jab')."',
            jab_max = '".$request->input('jab_max')."' WHERE kode_gol = '".$request->input('kode_gol')."' 
            AND kode_lokasi = '".$kode_lokasi."'";
            
            DB::connection($this->db)->update($update);
            
            $success['status'] = true;
            $success['message'] = "Data pajak karyawan berhasil diubah";
            $success['kode'] = $request->kode_pajak;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data pajak karyawan gagal diubah ".$e;
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
            'kode_pajak' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('hr_pajak')
            ->where('kode_pajak', $request->kode_pajak)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data pajak karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data pajak karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
