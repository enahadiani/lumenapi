<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class ModulController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_modul)){
                if($request->kode_modul == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and kode_modul='$request->kode_modul' ";
                }  
            }else{
                $filter .="";
            }

            $sql = "select kode_modul, nama, 
            case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status 
            from sai_modul where kode_lokasi= '".$kode_lokasi."' $filter";

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
            'nama' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $req = $request->all();

            $str_format="000";
            $prefix="MD";
            $sql2=  "select right(isnull(max(kode_modul),'".$prefix."000'),".strlen($str_format).")+1 as id from sai_modul where kode_modul like '$prefix%' and kode_lokasi='".$kode_lokasi."'";
            $query = DB::connection($this->sql)->select($sql2);
    
            $id = $prefix.str_pad($query[0]->id, strlen($str_format), $str_format, STR_PAD_LEFT);
            
            $insert = "insert into sai_modul (kode_lokasi, kode_modul, nama, tgl_input) 
            values('".$kode_lokasi."', '".$id."', '".$req['nama']."', getdate())";
            DB::connection($this->sql)->insert($insert);

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Modul berhasil disimpan";

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Modul gagal disimpan ".$e;
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
            'kode_modul' => 'required',
            'nama' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $req = $request->all();

            $del = DB::connection($this->sql)->table('sai_modul')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_modul', $request->kode_modul)
            ->delete();

            $insert = "insert into sai_modul (kode_lokasi, kode_modul, nama, tgl_input) 
            values('".$kode_lokasi."', '".$request->kode_modul."', '".$req['nama']."', getdate())";
            DB::connection($this->sql)->insert($insert);
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Modul berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Modul gagal diubah ".$e;
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
            'kode_modul' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('sai_modul')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_modul', $request->kode_modul)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Modul berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Modul gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
