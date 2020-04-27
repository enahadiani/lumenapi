<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HakaksesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    function isUnik($isi){
        if($data =  Auth::guard('admin')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select nik from hakakses where nik = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
        $auth = DB::connection('sqlsrv2')->select($strSQL);
        $auth = json_decode(json_encode($auth),true);
    
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
        return $res;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select nik,nama,kode_klp_menu,kode_lokasi,status_admin from hakakses where kode_lokasi='".$kode_lokasi."'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
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
            'nik' => 'required',
            'nama' => 'required',
            'kode_klp' => 'required',
            'pass' => 'required',
            'status_admin' => 'required',
            'klp_akses' => 'required',
            'path_view'=> 'required',
            'menu_mobile'=> 'required',
            'kode_menu_lab'=> 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(!$this->isUnik($request->nik)){
                $tmp=" error:Duplicate Entry. NIK sudah terdaftar di database !";
                $sts=false;
            }else{
                $sts= true;
            }
            
            if($sts){

                $ins = DB::connection('sqlsrv2')->insert('insert into hakakses(nik,nama,kode_lokasi,kode_klp_menu,pass,status_admin,klp_akses,path_view,menu_mobile,kode_menu_lab,password) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('nik'),$request->input('nama'),$kode_lokasi,$request->input('kode_klp_menu'),$request->input('pass'),$request->input('status_admin'),$request->input('klp_akses'),$request->input('path_view'),$request->input('menu_mobile'),$request->input('kode_menu_lab'),app('hash')->make($request->pass)]);
                
                DB::connection('sqlsrv2')->commit();
                $success['status'] = true;
                $success['message'] = "Data Hakakses berhasil disimpan";
            }else{
                $success['status'] = $sts;
                $success['message'] = $tmp;
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show($nik)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select nik,nama,kode_klp_menu,pass,status_admin,klp_akses,menu_mobile,path_view,kode_menu_lab from hakakses where kode_lokasi='".$kode_lokasi."' and nik='$nik'
            ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
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
    public function update(Request $request, $nik)
    {
        $this->validate($request, [
            'nama' => 'required',
            'kode_klp' => 'required',
            'pass' => 'required',
            'status_admin' => 'required',
            'klp_akses' => 'required',
            'path_view'=> 'required',
            'menu_mobile'=> 'required',
            'kode_menu_lab'=> 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrv2')->table('hakakses')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            $ins = DB::connection('sqlsrv2')->insert('insert into hakakses(nik,nama,kode_lokasi,kode_klp_menu,pass,status_admin,klp_akses,path_view,menu_mobile,kode_menu_lab,password) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$nik,$request->input('nama'),$kode_lokasi,$request->input('kode_klp_menu'),$request->input('pass'),$request->input('status_admin'),$request->input('klp_akses'),$request->input('path_view'),$request->input('menu_mobile'),$request->input('kode_menu_lab'),app('hash')->make($request->pass)]);

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Hakakses berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($kode_pp)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrv2')->table('hakakses')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Hakakses berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
