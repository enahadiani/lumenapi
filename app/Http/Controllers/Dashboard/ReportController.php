<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Menu;

class ReportController extends Controller
{
    public $successStatus = 200;

    public function getLokasi(){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            $res = DB::connection('sqlsrvyptkug')->select("select a.kode_lokasi,a.nama
            from lokasi a
            where a.kode_lokasi='$kode_lokasi' 
            order by a.kode_lokasi            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    
    
}
