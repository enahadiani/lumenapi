<?php

namespace App\Http\Controllers\Ypt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransferDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $sql = 'sqlsrvyptkug';

    public function getPeriode(Request $request)
    {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql= "select periode from periode where kode_lokasi='".$kode_lokasi."' order by periode desc";

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

    public function store(Request $request)
    {
        $this->validate($request, [            
            'periode' => 'required',           
            'kode_fs' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $exec1 = DB::connection($this->sql)->update("exec sp_exs_proses '$kode_lokasi','$request->periode','$request->kode_fs'");
            // $exec2 = DB::connection($this->sql)->update("exec sp_exs_proses_trans '$kode_lokasi','$request->periode'");
            // $exec3 = DB::connection($this->sql)->update("exec sp_exs_proses_lap '$kode_lokasi','$request->periode','$request->kode_fs' ");

            // $sql= "select kode_proses,nama,'0' as status from exs_proses_m where kode_lokasi='".$kode_lokasi."' ";

            $sql= "select 'P01' as kode_proses, 'Transfer data' as nama,'0' as status ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['data'] = $res;
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Transfer Data berhasil";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Transfer Data gagal ".$e;
            return response()->json($success, $this->successStatus); 
        }				 
        
    }
    
}
