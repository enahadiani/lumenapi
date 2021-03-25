<?php

namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth; 

class FilterController extends Controller {

    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getJenisUpload(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql= "select kode_jenis, nama 
            from java_jenis where kode_lokasi='".$kode_lokasi."'";

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

    public function getNoBukti(Request $request) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->no_proyek)){
                if($request->no_proyek != "" ){

                    $filter = " and no_proyek='$request->proyek' ";
                }else{
                    $filter = "";
                }
            }elseif(isset($request->kode_cust)) {
                if($request->kode_cust != "" ){

                    $filter = " and kode_cust='$request->kode_cust' ";
                }else{
                    $filter = "";
                }
            } 
            else{
                $filter = "";
            }

            $sql= "select no_proyek, keterangan 
            from java_proyek where kode_lokasi='".$kode_lokasi."' $filter";

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

?>