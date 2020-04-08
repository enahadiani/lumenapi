<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

class UserDeviceController extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware();
    // }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    
    public function index()
    {
        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv';

        }else if($data =  Auth::guard('admin')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv2';
           
        }else if($data =  Auth::guard('ypt')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrvypt';
           
        }
        if($db != ""){

            $user = DB::connection($db)->select("select nik,device_os,device_status,device_token,device_model,device_version,device_uuid,kode_lokasi 
            from user_device where kode_lokasi='$kode_lokasi'
            ");
            $user = json_decode(json_encode($user),true);
    
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                
                $user_device = $user;
            }
            else{
                
                $user_device = [];
            }
    
            return response()->json(['user_device' => $user_device], 200);
        }

    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'device_os' => 'required',
            'device_status' => 'required',
            'device_token' => 'required',
            'device_model' => 'required',
            'device_version' => 'required',
            'device_uuid' => 'required'
        ]);

        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv';
        }else if($data =  Auth::guard('admin')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv2';
           
        }else if($data =  Auth::guard('ypt')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrvypt';
           
        }

        if($db != ""){

            DB::connection($db)->beginTransaction();
            
            try {
                $ins = DB::connection($db)->insert('insert into user_device (nik,device_os,device_status,device_token,device_model,device_version,device_uuid,kode_lokasi)  values (?, ?, ?, ?, ?, ?, ?, ?)', [$nik,$request->input('device_os'),$request->input('device_status'),$request->input('device_token'),$request->input('device_model'),$request->input('device_version'),$request->input('device_uuid'),$kode_lokasi]);
            
                DB::connection($db)->commit();
                $success['status'] = true;
                $success['message'] = "Data user device berhasil disimpan";
                
                return response()->json($success, 200);
            } catch (\Throwable $e) {
                DB::connection($db)->rollback();
                $success['status'] = false;
                $success['message'] = "Data user device gagal disimpan ".$e;
                return response()->json($success, 200);
            }	
        }
    }

    public function show($nik)
    {
        if($data =  Auth::guard('user')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv';

        }else if($data =  Auth::guard('admin')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv2';
           
        }else if($data =  Auth::guard('ypt')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrvypt';
           
        }
        if($db != ""){

            $user = DB::connection($db)->select("select nik,device_os,device_status,device_token,device_model,device_version,device_uuid,kode_lokasi 
            from user_device where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $user = json_decode(json_encode($user),true);
    
            if(count($user) > 0){ //mengecek apakah data kosong atau tidak
                
                $user_device = $user;
            }
            else{
                
                $user_device = [];
            }
    
            return response()->json(['user_device' => $user_device], 200);
        }

    }

    public function update(Request $request,$nik)
    {
        $this->validate($request, [
            'device_os' => 'required',
            'device_status' => 'required',
            'device_token' => 'required',
            'device_model' => 'required',
            'device_version' => 'required',
            'device_uuid' => 'required'
        ]);

        if($data =  Auth::guard('user')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv';
        }else if($data =  Auth::guard('admin')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv2';
           
        }else if($data =  Auth::guard('ypt')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrvypt';
           
        }

        if($db != ""){

            DB::connection($db)->beginTransaction();
            
            try {
                $del = DB::connection($db)->table('user_device')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

                $ins = DB::connection($db)->insert('insert into user_device (nik,device_os,device_status,device_token,device_model,device_version,device_uuid,kode_lokasi)  values (?, ?, ?, ?, ?, ?, ?, ?)', [$nik,$request->input('device_os'),$request->input('device_status'),$request->input('device_token'),$request->input('device_model'),$request->input('device_version'),$request->input('device_uuid'),$kode_lokasi]);
            
                DB::connection($db)->commit();
                $success['status'] = true;
                $success['message'] = "Data user device berhasil diubah";
                
                return response()->json($success, 200);
            } catch (\Throwable $e) {
                DB::connection($db)->rollback();
                $success['status'] = false;
                $success['message'] = "Data user device gagal diubah ".$e;
                return response()->json($success, 200);
            }	
        }
    }

    public function destroy($nik)
    {
        

        if($data =  Auth::guard('user')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv';
        }else if($data =  Auth::guard('admin')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrv2';
           
        }else if($data =  Auth::guard('ypt')->user()){
            
            $kode_lokasi= $data->kode_lokasi;
            $db = 'sqlsrvypt';
           
        }

        if($db != ""){

            DB::connection($db)->beginTransaction();
            
            try {
                $del = DB::connection($db)->table('user_device')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

                DB::connection($db)->commit();
                $success['status'] = true;
                $success['message'] = "Data user device berhasil dihapus";
                
                return response()->json($success, 200);
            } catch (\Throwable $e) {
                DB::connection($db)->rollback();
                $success['status'] = false;
                $success['message'] = "Data user device gagal dihapus ".$e;
                return response()->json($success, 200);
            }	
        }
    }

   
}
