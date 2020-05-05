<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class NotifikasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function register(Request $request)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select nik,kode_lokasi from api_token_auth where nik='".$nik."' and kode_lokasi='".$kode_lokasi."' and token='".$request->token."' ");
            $res = json_decode(json_encode($res),true);

            if(count($res)>0){
                $success['message'] = 'Already registered';
            }else{
                $token_sql = DB::connection('sqlsrv2')->insert('insert into api_token_auth (nik,api_key,token,kode_lokasi,os,ver,model,uuid,tgl_login) values (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$nik,Str::random('alnum',20),$request->token,$kode_lokasi,'BROWSER','',
                '','',data('Y-m-d H:i:s')]);
                if($token_sql){
                    $success['message'] = "ID registered";
                }else{
                    $success['message'] = "Failed to register";
                }
            }
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
        
    }

}
