<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KotaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvrtrw';
    public $guard = 'rtrw';

    public function isUnik($isi){
        
        $auth = DB::connection($this->db)->select("select kode_kota from rt_kota where kode_kota ='".$isi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->kode_kota)){
                if($request->kode_kota == "all"){
                    $filter = "";
                }else{
                    $filter = "where kode_kota='$request->kode_kota' ";
                }
                $sql= "select a.kode_kota,a.nama,a.kode_prop,b.nama as nama_prop 
                from rt_kota a
                inner join rt_prop b on a.kode_prop=b.kode_prop   
                $filter";
            }else{
                $sql = "select a.kode_kota,a.nama,a.kode_prop,b.nama as nama_prop 
                from rt_kota a
                inner join rt_prop b on a.kode_prop=b.kode_prop  ";
            }

            $res = DB::connection($this->db)->select($sql);
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

    /**
     * Show the from for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_kota' => 'required',
            'nama' => 'required',
            'kode_prop' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_kota)){
                $ins = DB::connection($this->db)->insert("insert into rt_kota(kode_kota,nama,kode_prop) values ('".$request->kode_kota."','".$request->nama."','".$request->kode_prop."') ");
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Kota berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Kota sudah ada di database!";
            }
            $success['kode'] = $request->kode_kota;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kota gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the from for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_kota' => 'required',
            'nama' => 'required',
            'kode_prop' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_kota')
            ->where('kode_kota', $request->kode_kota)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into rt_kota(kode_kota,nama,kode_prop) values ('".$request->kode_kota."','".$request->nama."','".$request->kode_prop."') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kota berhasil diubah";
            $success['kode'] = $request->kode_kota;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kota gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_kota' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_kota')
            ->where('kode_kota', $request->kode_kota)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kota berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kota gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
