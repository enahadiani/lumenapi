<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class DinasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function isUnik($isi){
        
        $auth = DB::connection($this->db)->select("select no_sk from hr_sk where no_sk ='".$isi."' ");
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
            if(isset($request->no_sk)){
                if($request->no_sk == "all"){
                    $filter = "";
                }else{
                    $filter = "where no_sk='$request->no_sk' ";
                }
                $sql= "select no_sk,nama,convert(varchar,tgl_sk,103) as tgl_sk from hr_sk  $filter";
            }else{
                $sql = "select no_sk,nama,convert(varchar,tgl_sk,103) as tgl_sk from hr_sk ";
            }

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the from for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'no_sk' => 'required',
            'nama' => 'required',
            'tgl_sk' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->no_sk)){
                $sqlnu= "select max(nu) as nu from hr_sk where nik='$nik' and kode_lokasi='$kode_lokasi'  ";
                $rsnu=DB::connection($this->db)->select($sqlnu);

                if(count($rsnu) > 0){
                    $nu = $rsnu[0]->nu + 1;
                }else{
                    $nu = 0;
                }

                $ins = DB::connection($this->db)->insert("insert into hr_sk(no_sk,nama,tgl_sk,nu,kode_lokasi,nik) values ('".$request->no_sk."','".$request->nama."','".$request->tgl_sk."','".$nu."','$kode_lokasi','$nik') ");
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Dinas berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NO SK sudah ada di database!";
            }
            $success['kode'] = $request->no_sk;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dinas gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the from for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
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
            'no_sk' => 'required',
            'nama' => 'required',
            'tgl_sk' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sqlnu= "select nu from hr_sk where nik='$nik' and kode_lokasi='$kode_lokasi'  ";
            $rsnu=DB::connection($this->db)->select($sqlnu);
            
            if(count($rsnu) > 0){
                $nu = $rsnu[0]->nu;
            }else{
                $nu = 0;
            }
                
            $del = DB::connection($this->db)->table('hr_sk')
                ->where('no_sk', $request->no_sk)
                ->where('nik', $nik)
                ->delete();
                
            $ins = DB::connection($this->db)->insert("insert into hr_sk(no_sk,nama,tgl_sk,nu,kode_lokasi,nik) values ('".$request->no_sk."','".$request->nama."','".$request->tgl_sk."','".$nu."','$kode_lokasi','$nik') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Dinas berhasil diubah";
            $success['kode'] = $request->no_sk;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dinas gagal diubah ".$e;
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
            'no_sk' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('hr_sk')
            ->where('no_sk', $request->no_sk)
            ->where('nik', $nik)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Dinas berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dinas gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
