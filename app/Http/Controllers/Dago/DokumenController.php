<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DokumenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection('sqlsrvdago')->select("select no_dokumen from dgw_dok where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->no_dokumen)){
                if($request->no_dokumen == "all"){
                    $filter = "";
                }else{

                    $filter = " and no_dokumen='$request->no_dokumen' ";
                }
            }else{
                $filter = "";
            }

            $res = DB::connection('sqlsrvdago')->select( "select no_dokumen,deskripsi,jenis from dgw_dok where kode_lokasi='".$kode_lokasi."' $filter ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
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
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'jenis' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->no_dokumen,$kode_lokasi)){

                $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_dok(no_dokumen,deskripsi,kode_lokasi,jenis) values values (?, ?, ?)', array($request->no_dokumen,$request->deskripsi,$kode_lokasi,$request->jenis));
                
                DB::connection('sqlsrvdago')->commit();
                $success['status'] = true;
                $success['message'] = "Data Dokumen berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Id Dokumen sudah ada di database!";
            }
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'jenis' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_dokumen', $request->no_dokumen)
            ->delete();

            $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_dok(no_dokumen,deskripsi,kode_lokasi,jenis) values values (?, ?, ?)', array($request->no_dokumen,$request->deskripsi,$kode_lokasi,$request->jenis));
            
            DB::connection('sqlsrvdago')->commit();
            $success['status'] = true;
            $success['message'] = "Data Dokumen berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
            'no_dokumen' => 'required'
        ]);
        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_dokumen', $request->no_dokumen)
            ->delete();

            DB::connection('sqlsrvdago')->commit();
            $success['status'] = true;
            $success['message'] = "Data Dokumen berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }
}
