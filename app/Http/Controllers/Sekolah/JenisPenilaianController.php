<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JenisPenilaianController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

    public function isUnik($isi,$kode_lokasi,$kode_pp){
        
        $auth = DB::connection($this->db)->select("select kode_jenis from sis_jenisnilai where kode_jenis ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'");
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
            if(isset($request->kode_pp)){
                $filter = "and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter = "";
            }

            if(isset($request->kode_jenis)){
                $filter .= "and a.kode_jenis='$request->kode_jenis' ";
            }else{
                $filter .= "";
            }

            
            if(isset($request->flag_aktif)){
                $filter .= "and a.flag_aktif='$request->flag_aktif' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.kode_jenis, a.nama,a.kode_pp,a.tgl_input,case a.flag_aktif when 1 then 'AKTIF' else 'NONAKTIF' end as flag_aktif,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.kode_pp+'-'+b.nama as pp 
            from sis_jenisnilai a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."'  $filter ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the form for creating a new resource.
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
            'kode_pp' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_jenis,$kode_lokasi,$request->kode_pp)){

                $ins = DB::connection($this->db)->insert("insert into sis_jenisnilai(kode_jenis,nama,kode_lokasi,kode_pp,flag_aktif,tgl_input) values ('$request->kode_jenis','$request->nama','$kode_lokasi','$request->kode_pp','$request->flag_aktif',getdate()) ");
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['kode'] = $request->kode_jenis;
                $success['message'] = "Data Jenis Penilaian berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = $request->kode_jenis;
                $success['jenis'] = 'duplicate';
                $success['message'] = "Error : Duplicate entry. Kode Jenis Penilaian sudah ada di database!";
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Penilaian gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_jenis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $kode_jenis= $request->kode_jenis;

            $sql = "select a.kode_jenis, a.nama,a.kode_pp,a.tgl_input,a.flag_aktif,b.nama as nama_pp 
            from sis_jenisnilai a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi where a.kode_jenis ='".$kode_jenis."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            $success['sql'] = $sql;
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Show the form for editing the specified resource.
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
            'kode_pp' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_jenisnilai')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_jenis', $request->kode_jenis)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into sis_jenisnilai(kode_jenis,nama,kode_lokasi,kode_pp,flag_aktif,tgl_input) values ('$request->kode_jenis','$request->nama','$kode_lokasi','$request->kode_pp','$request->flag_aktif',getdate()) ");   
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_jenis;
            $success['message'] = "Data Jenis Penilaian berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Penilaian gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
            'kode_pp' => 'required',
            'kode_jenis' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_jenisnilai')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_jenis', $request->kode_jenis)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jenis Penilaian berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Penilaian gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
