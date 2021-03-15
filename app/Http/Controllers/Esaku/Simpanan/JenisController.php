<?php

namespace App\Http\Controllers\Esaku\Simpanan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JenisController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_param from kop_simp_param where kode_param ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            if(isset($request->kode_param)){
                if($request->kode_param == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_param='$request->kode_param' ";
                }
                $sql= "select a.kode_param,a.nama,a.kode_lokasi,a.akun_piutang,a.akun_titip,a.jenis,a.nilai,a.p_bunga,a.nu 
                from kop_simp_param a 
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select a.kode_param,a.nama,a.akun_piutang,a.akun_titip,a.jenis,a.nilai,a.p_bunga,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.tgl_input from kop_simp_param a where a.kode_lokasi= '".$kode_lokasi."'";
            }

            $res = DB::connection($this->sql)->select($sql);
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
     * Show the form for creating a new resource.
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
            'kode_param' =>'required|max:10',
            'nama' =>'required|max:50',
            'akun_piutang' =>'required|max:20',
            'akun_titip' =>'required|max:20',
            'jenis' =>'required|max:10|in:SW,SP,SS',
            'nilai' =>'required',
            'p_bunga' =>'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($request->kode_param,$kode_lokasi)){

                if ($request->jenis == "SP") $nu = 1;
                if ($request->jenis == "SW") $nu = 2;
                if ($request->jenis == "SS") $nu = 3;
                
                $ins = DB::connection($this->sql)->insert("insert into kop_simp_param(kode_param,nama,kode_lokasi,akun_piutang,akun_titip,jenis,nilai,p_bunga,nu,tgl_input) values ('".$request->kode_param."','".$request->nama."','".$kode_lokasi."','".$request->akun_piutang."','".$request->akun_titip."','".$request->jenis."','".$request->nilai."','".$request->p_bunga."','".$nu."',getdate()) ");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $request->kode_param;
                $success['message'] = "Data Jenis Simpanan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No Jenis Simpanan sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Simpanan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
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
            'kode_param' =>'required|max:10',
            'nama' =>'required|max:50',
            'akun_piutang' =>'required|max:20',
            'akun_titip' =>'required|max:20',
            'jenis' =>'required|max:10|in:SW,SP,SS',
            'nilai' =>'required',
            'p_bunga' =>'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('kop_simp_param')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_param', $request->kode_param)
            ->delete();

            if ($request->jenis == "SP") $nu = 1;
            if ($request->jenis == "SW") $nu = 2;
            if ($request->jenis == "SS") $nu = 3;
            
            $ins = DB::connection($this->sql)->insert("insert into kop_simp_param(kode_param,nama,kode_lokasi,akun_piutang,akun_titip,jenis,nilai,p_bunga,nu,tgl_input) values ('".$request->kode_param."','".$request->nama."','".$kode_lokasi."','".$request->akun_piutang."','".$request->akun_titip."','".$request->jenis."','".$request->nilai."','".$request->p_bunga."','".$nu."',getdate()) ");
            
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_param;
            $success['message'] = "Data Jenis Simpanan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['kode'] = "-";
            $success['message'] = "Data Jenis Simpanan gagal diubah ".$e;
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
            'kode_param' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('kop_simp_param')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_param', $request->kode_param)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jenis Simpanan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Simpanan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getAkun(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_akun)){
                if($request->kode_akun != "" ){

                    $filter = " and a.kode_akun='$request->kode_akun' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql = "select a.kode_akun, a.nama from masakun a 
            where a.kode_lokasi='$kode_lokasi' $filter ";

            $res = DB::connection($this->sql)->select($sql);
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

    
}
