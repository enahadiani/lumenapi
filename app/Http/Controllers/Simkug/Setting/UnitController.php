<?php

namespace App\Http\Controllers\Simkug\Setting;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class UnitController extends Controller
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
        
        $auth = DB::connection($this->db)->select("select kode_pp from pp where kode_pp ='".$isi."' ");
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

            if(isset($request->kode_lokasi)){
                $kode_lokasi = $request->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_pp)){
                if($request->kode_pp == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and kode_pp='$request->kode_pp' ";
                }
            }
            if(isset($request->tipe)){
                if($request->tipe == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and tipe='$request->tipe' ";
                }
            }
            
            $sql= "select kode_pp,nama,flag_aktif,kode_lokasi from pp  where kode_lokasi <> '00' $filter ";
            
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
            'kode_lokasi' => 'required',
            'kode_pp' => 'required',
            'nama' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_pp)){
                $ins = DB::connection($this->db)->insert("insert into pp(kode_pp,nama,flag_aktif,kode_lokasi,level_spasi,rowindex) values (?, ?, ?, ?, ?, ?) ",array($request->kode_pp,$request->nama,$request->flag_aktif,$request->kode_lokasi,'-',0));
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['kode'] = $request->kode_pp;
                $success['message'] = "Data Unit berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = '-';
                $success['jenis'] = 'duplicate';
                $success['message'] = "Error : Duplicate entry. Kode Unit sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Unit gagal disimpan ".$e;
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
            'kode_lokasi' => 'required',
            'kode_pp' => 'required',
            'nama' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ins = DB::connection($this->db)->insert("update pp set nama=?,flag_aktif=?
            where kode_pp=? ",array($request->nama,$request->flag_aktif,$request->kode_pp));
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_pp;
            $success['message'] = "Data Unit berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Unit gagal diubah ".$e;
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
            'kode_pp' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('pp')
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Unit berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Unit gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
