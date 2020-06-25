<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReferensiTransController extends Controller
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

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_ref from trans_ref where kode_ref ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->jenis)){
                if($request->jenis == "all" || $request->jenis == ""){
                    $filter .= "";
                }else{

                    $filter .= " and a.jenis ='$request->jenis' ";
                }
            }else{
                $filter .= "";
            }

            $sql = "select a.kode_ref,a.nama,a.akun_debet,a.akun_kredit,a.jenis,a.kode_pp,a.kode_pp+' | '+b.nama as pp 
            from trans_ref a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            where a.kode_lokasi='".$kode_lokasi."' $filter order by a.kode_ref";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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
            'kode_ref' => 'required',
            'nama' => 'required',
            'akun_debet' => 'required',
            'akun_kredit' => 'required',
            'jenis' => 'required',
            'kode_pp' => 'required'
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

            if($this->isUnik($request->kode_ref,$kode_lokasi)){
                $ins = DB::connection($this->sql)->insert("insert into trans_ref (kode_ref,nama,akun_debet,akun_kredit,jenis,kode_pp) values ('$request->kode_ref','$request->nama','$request->akun_debet','$request->akun_kredit','$request->jenis','$request->kode_pp') ");

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Referensi Transaksi berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Ref sudah ada di database!";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Referensi Transaksi gagal disimpan ".$e;
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
            'kode_ref' => 'required'
        ]);
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            $akun = DB::connection($this->sql)->select("select a.kode_ref,a.nama,a.akun_debet,a.akun_kredit,a.jenis,a.kode_pp,b.nama as nama_pp
            from trans_ref a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_ref='$request->kode_ref'				 
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
            'kode_ref' => 'required',
            'nama' => 'required',
            'akun_debet' => 'required',
            'akun_kredit' => 'required',
            'jenis' => 'required',
            'kode_pp' => 'required'
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

            $del = DB::connection($this->sql)->table('trans_ref')->where('kode_lokasi', $kode_lokasi)->where('kode_ref', $request->kode_ref)->delete();

            $ins = DB::connection($this->sql)->insert("insert into trans_ref (kode_ref,nama,akun_debet,akun_kredit,jenis,kode_pp) values ('$request->kode_ref','$request->nama','$request->akun_debet','$request->akun_kredit','$request->jenis','$request->kode_pp') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Referensi Transaksi berhasil diubah";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Referensi Transaksi gagal diubah ".$e;
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
            'kode_ref' => 'required'
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
            
            $del = DB::connection($this->sql)->table('trans_ref')->where('kode_lokasi', $kode_lokasi)->where('kode_ref', $request->kode_ref)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Referensi Transaksi berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Referensi Transaksi gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
