<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class SanksiController extends Controller
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
        
        $auth = DB::connection($this->db)->select("select no_sk from hr_sanksi where no_sk ='".$isi."' ");
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
            if(isset($request->nu)){
                if($request->nu == "all"){
                    $filter = "";
                }else{
                    $filter = "and nu='$request->nu' ";
                }
                $sql= "select nama,convert(varchar,tanggal,103) as tgl,jenis from hr_sanksi
                where kode_lokasi='$kode_lokasi' and nik='$nik' $filter";
            }else{
                $sql = "select nama,convert(varchar,tanggal,103) as tgl,jenis from hr_sanksi
                where kode_lokasi='$kode_lokasi' and nik='$nik' ";
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
            'jenis' => 'required',
            'nama' => 'required',
            'tanggal' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sqlnu= "select max(nu) as nu from hr_sanksi where nik='$nik' and kode_lokasi='$kode_lokasi'  ";
            $rsnu=DB::connection($this->db)->select($sqlnu);

            if(count($rsnu) > 0){
                $nu = $rsnu[0]->nu + 1;
            }else{
                $nu = 0;
            }

            $ins = DB::connection($this->db)->insert("insert into hr_sanksi(jenis,nama,tanggal,nu,kode_lokasi,nik) values ('".$request->jenis."','".$request->nama."','".$request->tanggal."','".$nu."','$kode_lokasi','$nik') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Sanksi berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Sanksi gagal disimpan ".$e;
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
            'jenis' => 'required',
            'nama' => 'required',
            'tanggal' => 'required',
            'nu' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sqlnu= "select nu from hr_sanksi where nik='$nik' and kode_lokasi='$kode_lokasi'  ";
            $rsnu=DB::connection($this->db)->select($sqlnu);
            
            if(count($rsnu) > 0){
                $nu = $rsnu[0]->nu;
            }else{
                $nu = 0;
            }
                
            $del = DB::connection($this->db)->table('hr_sanksi')
                ->where('nik', $request->nik)
                ->where('nu', $request->nu)
                ->delete();

            $ins = DB::connection($this->db)->insert("insert into hr_sanksi(jenis,nama,tanggal,nu,kode_lokasi,nik) values ('".$request->jenis."','".$request->nama."','".$request->tanggal."','".$nu."','$kode_lokasi','$nik') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Sanksi berhasil diubah";
            $success['kode'] = $request->no_sk;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Sanksi gagal diubah ".$e;
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
            
            $del = DB::connection($this->db)->table('hr_sanksi')
            ->where('no_sk', $request->no_sk)
            ->where('nik', $nik)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Sanksi berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Sanksi gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
