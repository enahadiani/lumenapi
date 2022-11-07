<?php

namespace App\Http\Controllers\Sju;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public $successStatus = 200;
    public $db = "sqlsrvsju";
    public $guard = "sju";

    public function getDataPolis(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter_periode = "";
            if(isset($r->periode) && $r->periode != ""){
                $filter_periode = " and periode='$r->periode' ";
            }

            $res = DB::connection($this->db)->select("select * from sju_polis_m 
            where kode_lokasi='$kode_lokasi' $filter_periode
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data']= [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
   
}
