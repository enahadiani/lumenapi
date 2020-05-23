<?php

namespace App\Http\Controllers\Gl;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LokasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
           
            $res = DB::connection('sqlsrv2')->select( "select kode_lokasi,nama,flag_aktif from lokasi ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
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
            'kode_lokasi' => 'required',
            'nama' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
           
            $ins = DB::connection('sqlsrv2')->insert('insert into lokasi(kode_lokasi,nama,flag_aktif) values (?, ?, ?)', array($request->kode_lokasi,$request->nama,$request->flag_aktif));
                
            DB::connection('sqlsrv2')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data  berhasil disimpan";
           
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data  gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
     public function show($kode_lokasi)
     {
         try {
             
             
             if($data =  Auth::guard('admin')->user()){
                 $nik_user= $data->nik;
             }
 
             $sql = "select kode_lokasi,nama,flag_aktif from lokasi where kode_lokasi='".$kode_lokasi."'";
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
     public function update(Request $request, $kode_lokasi)
     {
         $this->validate($request, [
             'nama' => 'required',
             'flag_aktif' => 'required'
         ]);
 
         DB::connection('sqlsrv2')->beginTransaction();
         
         try {
             if($data =  Auth::guard('admin')->user()){
                 $nik_user= $data->nik;
             }
             
             $del = DB::connection('sqlsrv2')->table('lokasi')->where('kode_lokasi', $kode_lokasi)->delete();
 
             $ins = DB::connection('sqlsrv2')->insert('insert into lokasi(kode_lokasi,nama,flag_aktif) values (?, ?, ?)', [$request->input('kode_lokasi'),$request->input('nama'),$request->input('flag_aktif')]);
 
             DB::connection('sqlsrv2')->commit();
             $success['status'] = true;
             $success['cek'] = $kode_lokasi;
             $success['message'] = "Data berhasil diubah";
             return response()->json(['success'=>$success], $this->successStatus); 
         } catch (\Throwable $e) {
             DB::connection('sqlsrv2')->rollback();
             $success['status'] = false;
             $success['message'] = "Data gagal diubah ".$e;
             return response()->json(['success'=>$success], $this->successStatus); 
         }	
     }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($kode_lokasi)
    {
       
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
            }
            
            $del = DB::connection('sqlsrv2')->table('lokasi')
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            DB::connection('sqlsrv2')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    
}
