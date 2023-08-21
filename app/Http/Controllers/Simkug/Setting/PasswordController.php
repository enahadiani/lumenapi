<?php

namespace App\Http\Controllers\Simkug\Setting;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PasswordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbsimkug';
    public $guard = 'simkug';

    public function isUnik($isi){
        
        $auth = DB::connection($this->db)->select("select kode_form from m_form where kode_form ='".$isi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_form' => 'required',
            'nama' => 'required',
            'form' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_form)){
                $ins = DB::connection($this->db)->insert("insert into m_form(kode_form,nama_form,form) values ('".$request->kode_form."','".$request->nama."','".$request->form."') ");
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Form berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Form sudah ada di database!";
            }
            $success['kode'] = $request->kode_form;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Form gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function updatePassword(Request $request){
        $this->validate($request,[
            'nik' => 'required',
            'password_lama' => 'required',
            'password_baru' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->beginTransaction();

            $cek =  DB::connection($this->db)->select("select pass from hakakses where nik=? and pass=? ",array($request->input('nik'),$request->input('password_lama')));
            if(count($cek) > 0){

                $upd =  DB::connection($this->db)->table('hakakses')
                ->where('nik', $request->input('nik'))
                ->where('pass', $request->input('password_lama'))
                ->update(['pass' => $request->input('password_baru'), 'password' => app('hash')->make($request->input('password_baru'))]);
                
                if($upd){ //mengecek apakah data kosong atau tidak
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['message'] = "Password berhasil diubah";
                    return response()->json($success, 200);     
                }
                else{
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['message'] = "Password gagal diubah";
                    return response()->json($success, 200);
                }
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = "Password lama tidak valid";
                return response()->json($success, 200);
            }
        }catch (\Throwable $e) {
            
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }
}
