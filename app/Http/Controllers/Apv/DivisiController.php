<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DivisiController extends Controller
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
    
        $strSQL = "select kode_divisi from apv_divisi where kode_divisi = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
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

            $res = DB::connection('sqlsrv2')->select("select kode_divisi,nama from apv_divisi where kode_lokasi='".$kode_lokasi."' 
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
            'kode_divisi' => 'required',
            'nama' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if($this->isUnik($request->kode_divisi)){

                $ins = DB::connection('sqlsrv2')->insert('insert into apv_divisi(kode_divisi,nama,kode_lokasi) values (?, ?, ?)', [$request->input('kode_divisi'),$request->input('nama'),$kode_lokasi]);
                
                DB::connection('sqlsrv2')->commit();
                $success['status'] = true;
                $success['message'] = "Data Divisi berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Divisi sudah ada di database!";
            }

            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Divisi gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show($kode_divisi)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select 
            kode_divisi,nama from apv_divisi where kode_lokasi='".$kode_lokasi."' and kode_divisi='$kode_divisi'
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
    public function update(Request $request, $kode_divisi)
    {
        $this->validate($request, [
            'nama' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrv2')->table('apv_divisi')->where('kode_lokasi', $kode_lokasi)->where('kode_divisi', $kode_divisi)->delete();

            $ins = DB::connection('sqlsrv2')->insert('insert into apv_divisi(kode_divisi,nama,kode_lokasi) values (?, ?, ?)', [$kode_divisi,$request->input('nama'),$kode_lokasi]);

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Divisi berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Divisi gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($kode_divisi)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrv2')->table('apv_divisi')->where('kode_lokasi', $kode_lokasi)->where('kode_divisi', $kode_divisi)->delete();

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Divisi berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Divisi gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
