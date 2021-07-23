<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class JenisIuranController extends Controller
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
        
        $auth = DB::connection($this->db)->select("select kode_jenis from rt_iuran_jenis where kode_jenis ='".$isi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "where a.kode_lokasi = '$kode_lokasi' ";
            if(isset($request->kode_jenis) && $request->kode_jenis != ""){
                $filter .= "and a.kode_jenis='$request->kode_jenis' ";
            }
            if(isset($request->jenis) && $request->jenis != ""){
                $filter .= "and a.jenis='$request->jenis' ";
            }

            
            $sql= "select a.kode_jenis,a.nama,a.jenis,a.status,convert(varchar,a.tgl_mulai,103) as tgl_mulai,convert(varchar,a.tgl_selesai,103) as tgl_selesai
            from rt_iuran_jenis a
            $filter";
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
            'kode_jenis' => 'required',
            'nama' => 'required',
            'jenis' => 'required',
            'status' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required' 
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_jenis)){
                $ins = DB::connection($this->db)->insert("insert into rt_iuran_jenis(kode_jenis,nama,jenis,status,tgl_mulai,tgl_selesai,kode_lokasi) values ('".$request->kode_jenis."','".$request->nama."','".$request->jenis."','".$request->status."','".$this->reverseDate($request->tgl_mulai,'/','-')."','".$this->reverseDate($request->tgl_selesai,'/','-')."','$kode_lokasi') ");
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Jenis Iuran berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Jenis Iuran sudah ada di database!";
            }
            $success['kode'] = $request->kode_jenis;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Iuran gagal disimpan ".$e;
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
            'kode_jenis' => 'required',
            'nama' => 'required',
            'jenis' => 'required',
            'status' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_iuran_jenis')
            ->where('kode_jenis', $request->kode_jenis)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into rt_iuran_jenis(kode_jenis,nama,jenis,status,tgl_mulai,tgl_selesai,kode_lokasi) values ('".$request->kode_jenis."','".$request->nama."','".$request->jenis."','".$request->status."','".$this->reverseDate($request->tgl_mulai,'/','-')."','".$this->reverseDate($request->tgl_selesai,'/','-')."','$kode_lokasi') ");
                
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jenis Iuran berhasil diubah";
            $success['kode'] = $request->kode_jenis;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Iuran gagal diubah ".$e;
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
            'kode_jenis' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_iuran_jenis')
            ->where('kode_jenis', $request->kode_jenis)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jenis Iuran berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Iuran gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
