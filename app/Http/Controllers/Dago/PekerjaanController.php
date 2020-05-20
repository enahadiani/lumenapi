<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PekerjaanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection('sqlsrvdago')->select("select id_pekerjaan from dgw_pekerjaan where id_pekerjaan ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            if(isset($request->id_pekerjaan)){
                if($request->id_pekerjaan == "all"){
                    $filter = "";
                }else{

                    $filter = " and id_pekerjaan='$request->id_pekerjaan' ";
                }
            }else{
                $filter = "";
            }

            $res = DB::connection('sqlsrvdago')->select( "select id_pekerjaan,nama from dgw_pekerjaan where kode_lokasi='".$kode_lokasi."' $filter ");
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
            'id_pekerjaan' => 'required',
            'nama' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->id_pekerjaan,$kode_lokasi)){

                $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_pekerjaan(id_pekerjaan,nama,kode_lokasi) values (?, ?, ?)', array($request->id_pekerjaan,$request->nama,$kode_lokasi));
                
                DB::connection('sqlsrvdago')->commit();
                $success['status'] = "SUCCESS";
                $success['message'] = "Data Pekerjaan berhasil disimpan";
            }else{
                $success['status'] = "FAILED";
                $success['message'] = "Error : Duplicate entry. Id Pekerjaan sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pekerjaan gagal disimpan ".$e;
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
            'id_pekerjaan' => 'required',
            'nama' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_pekerjaan')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id_pekerjaan', $request->id_pekerjaan)
            ->delete();

            $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_pekerjaan(id_pekerjaan,nama,kode_lokasi) values (?, ?, ?)', array($request->id_pekerjaan,$request->nama,$kode_lokasi));
            
            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Pekerjaan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pekerjaan gagal diubah ".$e;
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
            'id_pekerjaan' => 'required'
        ]);
        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_pekerjaan')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id_pekerjaan', $request->id_pekerjaan)
            ->delete();

            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Pekerjaan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pekerjaan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
