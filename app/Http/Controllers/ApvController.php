<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApvController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function pengajuan(){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            // $aju = DB::select("select * from yk_pb_m a 
            // where a.progress='1' and a.kode_lokasi='34' and a.modul in ('PBBAU','PBPR','PBINV') 					 
            // ");
            // $aju = json_decode(json_encode($aju),true);
            // $siswa = DevSiswa::all();
            
            // if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = [];
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            // }
            // else{
            //     $success['message'] = "Data Kosong!";
            //     $success['status'] = true;
                
            //     return response()->json(['success'=>$success], $this->successStatus);
            // }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
