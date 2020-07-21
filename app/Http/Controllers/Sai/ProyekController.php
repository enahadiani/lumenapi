<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class ProyekController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select no_proyek from sai_proyek where no_proyek = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
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

            $url = url('api/sai-auth/storage');
            $filter = "";
            if(isset($request->no_proyek)){
                if($request->no_proyek == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and no_proyek='$request->no_proyek' ";
                }   
                $sql = "select no_proyek,nama,kode_cust,convert(varchar(10),tgl_mulai,121) as tgl_mulai,convert(varchar(10),tgl_selesai,121) as tgl_selesai,case when file_dok != '-' then '".$url."/'+file_dok else '-' end as file_dok from sai_proyek where kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                
                $sql = "select no_proyek,nama,kode_cust,convert(varchar(10),tgl_mulai,103) as tgl_mulai,convert(varchar(10),tgl_selesai,103) as tgl_selesai from sai_proyek where kode_lokasi='".$kode_lokasi."' ";
            }

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
            'no_proyek' => 'required',
            'nama' => 'required',
            'kode_cust' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->nik)){

                $ins = DB::connection($this->sql)->insert("insert into sai_proyek(no_proyek,nama,kode_cust,tgl_mulai,kode_lokasi,tgl_selesai) values ('".$request->no_proyek."','".$request->nama."','".$request->kode_cust."','".$request->tgl_mulai."','".$kode_lokasi."','".$request->tgl_selesai."')");

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Proyek berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Proyek sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Proyek gagal disimpan ".$e;
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
            'no_proyek' => 'required',
            'nama' => 'required',
            'kode_cust' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('sai_proyek')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_proyek', $request->no_proyek)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into sai_proyek(no_proyek,nama,kode_cust,tgl_mulai,kode_lokasi,tgl_selesai) values ('".$request->no_proyek."','".$request->nama."','".$request->kode_cust."','".$request->tgl_mulai."','".$kode_lokasi."','".$request->tgl_selesai."')");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Proyek berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Proyek gagal diubah ".$e;
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
            'no_proyek' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('sai_proyek')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_proyek', $request->no_proyek)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Proyek berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Proyek gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
