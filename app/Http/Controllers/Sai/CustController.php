<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class CustController extends Controller
{
    
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select kode_cust from sai_cust where kode_cust = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
        $auth = DB::connection($this->sql)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);
    
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
        return $res;
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_cust)){
                if($request->kode_cust == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and a.kode_cust='$request->kode_cust' ";
                }   
                $sql="select a.kode_cust,a.nama,a.alamat,a.pic,jabatan_pic,a.email,a.no_telp from sai_cust a where a.kode_lokasi='".$kode_lokasi."' $filter ";
                $sql2="select a.kode_lampiran,b.nama from sai_cust_d a 
                inner join sai_lampiran b on a.kode_lampiran=b.kode_lampiran and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."' $filter 
                order by a.nu";
                $res2 = DB::connection($this->sql)->select($sql2);
                $res2 = json_decode(json_encode($res2),true);
                
            }else{
                
                $sql = "select kode_cust,nama,alamat,pic,jabatan_pic,email,no_telp from sai_cust where kode_lokasi='".$kode_lokasi."' ";
            }
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                if(isset($res2)){
                    $success['data_lampiran'] = $res2;
                }
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                if(isset($res2)){
                    $success['data_lampiran'] = [];
                }
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
            'kode_cust' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'pic' => 'required',
            'jabatan_pic' => 'required',
            'email' => 'required',
            'no_telp' => 'required'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($request->kode_cust)){                
                
                $ins = DB::connection($this->sql)->insert("insert into sai_cust(kode_cust,nama,alamat,pic,kode_lokasi,email,no_telp,jabatan_pic) values ('".$request->kode_cust."','".$request->nama."','".$request->alamat."','".$request->pic."','".$kode_lokasi."','".$request->email."','".$request->no_telp."','$request->jabatan_pic')");

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Customer berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Customer sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_cust' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'pic' => 'required',
            'jabatan_pic' => 'required',
            'email' => 'required',
            'no_telp' => 'required'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('sai_cust')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_cust', $request->kode_cust)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into sai_cust(kode_cust,nama,alamat,pic,kode_lokasi,email,no_telp,jabatan_pic) values ('".$request->kode_cust."','".$request->nama."','".$request->alamat."','".$request->pic."','".$kode_lokasi."','".$request->email."','".$request->no_telp."','$request->jabatan_pic')");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Customer berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_cust' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('sai_cust')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_cust', $request->kode_cust)
            ->delete();
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Customer berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
