<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JenisHargaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection('sqlsrvdago')->select("select kode_harga from dgw_jenis_harga where kode_harga ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            if(isset($request->kode_harga)){
                if($request->kode_harga == "all"){
                    $filter = "";
                }else{

                    $filter = " and kode_harga='$request->kode_harga' ";
                }
            }else{
                $filter = "";
            }

            $res = DB::connection('sqlsrvdago')->select( "select kode_harga,nama from dgw_jenis_harga where kode_lokasi='".$kode_lokasi."' $filter ");
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
            'kode_harga' => 'required',
            'nama' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_harga,$kode_lokasi)){

                $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_jenis_harga(kode_harga,nama,kode_lokasi) values values (?, ?, ?)', array($request->kode_harga,$request->nama,$kode_lokasi));
                
                DB::connection('sqlsrvdago')->commit();
                $success['status'] = "SUCCESS";
                $success['message'] = "Data Jenis Harga berhasil disimpan";
            }else{
                $success['status'] = "FAILED";
                $success['message'] = "Error : Duplicate entry. Id Jenis Harga sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Jenis Harga gagal disimpan ".$e;
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
            'kode_harga' => 'required',
            'nama' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_jenis_harga')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_harga', $request->kode_harga)
            ->delete();

            $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_jenis_harga(kode_harga,nama,kode_lokasi) values values (?, ?, ?)', array($request->kode_harga,$request->nama,$kode_lokasi));
            
            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Jenis Harga berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Jenis Harga gagal diubah ".$e;
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
            'kode_harga' => 'required'
        ]);
        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_jenis_harga')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_harga', $request->kode_harga)
            ->delete();

            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Jenis Harga berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Jenis Harga gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
