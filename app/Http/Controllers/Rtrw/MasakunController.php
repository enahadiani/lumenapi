<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MasakunController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrvrtrw';
    public $guard = 'rtrw';
    public $guard2 = 'satpam';

    public function index()
    {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $akun = DB::connection($this->sql)->select("select kode_akun,kode_lokasi,nama,modul,jenis,kode_curr,block,status_gar as gar,normal from masakun where kode_lokasi='$kode_lokasi'		 
            ");
            $akun = json_decode(json_encode($akun),true);
            
            if(count($akun) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $akun;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
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
            'kode_akun' => 'required',
            'nama' => 'required',
            'modul' => 'required',
            'jenis' => 'required',
            'kode_curr' => 'required',
            'block' => 'required',
            'status_gar' => 'required',
            'normal' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ins = DB::connection($this->sql)->insert("insert into masakun (kode_akun,kode_lokasi,nama,modul,jenis,kode_curr,block,status_gar,normal,kode_gar) values ('$request->kode_akun','$kode_lokasi','$request->nama','$request->modul','$request->jenis','$request->kode_curr','$request->block','$request->status_gar','$request->normal','$request->kode_akun') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_akun;
            $success['message'] = "Data Master akun berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['kode'] = '-';
            $success['message'] = "Data Master akun gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request,[
            'kode_akun' => 'required'
        ]);
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            $akun = DB::connection($this->sql)->select("select kode_akun,kode_lokasi,nama,modul,case modul when 'A' then 'AKTIVA' when 'P' then 'PASIVA' when 'L' then 'LABARUGI' else 'MODAL' end as nama_modul,jenis,kode_curr,block,status_gar as gar,normal from masakun where kode_lokasi='$kode_lokasi' and kode_akun='$request->kode_akun'				 
            ");

            $akun = json_decode(json_encode($akun),true);

            if(count($akun) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $akun;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] =[];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
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
            'kode_akun' => 'required',
            'nama' => 'required',
            'modul' => 'required',
            'jenis' => 'required',
            'kode_curr' => 'required',
            'block' => 'required',
            'status_gar' => 'required',
            'normal' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->sql)->table('masakun')->where('kode_lokasi', $kode_lokasi)->where('kode_akun', $request->kode_akun)->delete();

            $ins = DB::connection($this->sql)->insert("insert into masakun (kode_akun,kode_lokasi,nama,modul,jenis,kode_curr,block,status_gar,normal,kode_gar) values ('$request->kode_akun','$kode_lokasi','$request->nama','$request->modul','$request->jenis','$request->kode_curr','$request->block','$request->status_gar','$request->normal','$request->kode_akun') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_akun;
            $success['message'] = "Data Master akun berhasil diubah";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['kode'] = '-';
            $success['message'] = "Data Master akun gagal diubah ".$e;
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
            'kode_akun' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('masakun')->where('kode_lokasi', $kode_lokasi)->where('kode_akun', $request->kode_akun)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Masakun berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Masakun gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getCurrency()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $curr = DB::connection($this->sql)->select("select kode_curr from curr		 
            ");
            $curr = json_decode(json_encode($curr),true);
            
            if(count($curr) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $curr;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getModul()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $modul = DB::connection($this->sql)->select("select distinct kode_tipe,nama_tipe from tipe_neraca
            ");
            $modul = json_decode(json_encode($modul),true);
            
            if(count($modul) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $modul;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
