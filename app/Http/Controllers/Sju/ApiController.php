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

            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_sju_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'POLIS','dbnewsju'));
            // END SAVE

            $filter_periode = "";
            if(isset($r->periode) && $r->periode != ""){
                $filter_periode = " and periode='$r->periode' ";
            }

            $filter_no_polis = "";
            if(isset($r->no_polis) && $r->no_polis != ""){
                $filter_no_polis = " and no_dok='$r->no_polis' ";
            }

            $filter_kode_cust = "";
            if(isset($r->kode_cust) && $r->kode_cust != ""){
                $filter_kode_cust = " and kode_cust='$r->kode_cust' ";
            }

            $res = DB::connection($this->db)->select("select * from sju_polis_m 
            where kode_lokasi='$kode_lokasi' $filter_periode $filter_no_polis $filter_kode_cust
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

    public function getDataCOB(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_sju_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'COB','dbnewsju'));
            // END SAVE

            $res = DB::connection($this->db)->select("select * from sju_tipe 
            where kode_lokasi='$kode_lokasi'
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

    public function getDataTertanggung(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_sju_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'Tertanggung/Customer','dbnewsju'));
            // END SAVE

            $res = DB::connection($this->db)->select("select * from sju_cust 
            where kode_lokasi='$kode_lokasi'
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

    public function getDataPenanggung(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // SAVE LOG TO DB
            $log = print_r($r->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into api_sju_log (kode_lokasi,nik_user,tgl_input,datalog,nama_api,dbname)
            values (?, ?, getdate(), ?, ?, ?) ",array($kode_lokasi,$nik,$log,'Penanggung/Vendor','dbnewsju'));
            // END SAVE

            $res = DB::connection($this->db)->select("select * from sju_vendor 
            where kode_lokasi='$kode_lokasi'
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
