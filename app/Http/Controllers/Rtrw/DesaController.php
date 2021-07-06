<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class DesaController extends Controller
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
        
        $auth = DB::connection($this->db)->select("select kode_desa from rt_desa where kode_desa ='".$isi."' ");
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
            if(isset($request->kode_desa)){
                if($request->kode_desa == "all"){
                    $filter = "";
                }else{
                    $filter = "where kode_desa='$request->kode_desa' ";
                }
                $sql= "select a.kode_desa,a.nama,a.kode_camat,b.nama as nama_camat 
                from rt_desa a
                inner join rt_camat b on a.kode_camat=b.kode_camat   
                $filter";
            }else{
                $sql = "select a.kode_desa,a.nama,a.kode_camat,b.nama as nama_camat 
                from rt_desa a
                inner join rt_camat b on a.kode_camat=b.kode_camat  ";
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
            'kode_desa' => 'required',
            'nama' => 'required',
            'kode_camat' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_desa)){
                $ins = DB::connection($this->db)->insert("insert into rt_desa(kode_desa,nama,kode_camat) values ('".$request->kode_desa."','".$request->nama."','".$request->kode_camat."') ");
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Camat berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Camat sudah ada di database!";
            }
            $success['kode'] = $request->kode_desa;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Camat gagal disimpan ".$e;
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
            'kode_desa' => 'required',
            'nama' => 'required',
            'kode_camat' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_desa')
            ->where('kode_desa', $request->kode_desa)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into rt_desa(kode_desa,nama,kode_camat) values ('".$request->kode_desa."','".$request->nama."','".$request->kode_camat."') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Camat berhasil diubah";
            $success['kode'] = $request->kode_desa;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Camat gagal diubah ".$e;
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
            'kode_desa' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_desa')
            ->where('kode_desa', $request->kode_desa)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Camat berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Camat gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
