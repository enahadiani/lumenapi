<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MataPelajaranController extends Controller
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
        
        $auth = DB::connection($this->db)->select("select kode_matpel from sis_matpel where kode_matpel ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'");
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
            if(isset($request->kode_matpel)){
                $filter .= "and a.kode_matpel='$request->kode_matpel' ";
            }else{
                $filter .= "";
            }

            if(isset($request->sifat)){
                $filter .= "and a.sifat='$request->sifat' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.kode_matpel, a.nama,a.kode_pp+'-'+b.nama as pp,a.tgl_input,a.sifat,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,case a.flag_aktif when 1 then 'AKTIF' else 'NONAKTIF' end as flag_aktif,case a.sifat when '0' then 'Nasional' when '1' then 'Muatan Lokal' when '2' then 'Khusus' when '3' then 'Ekstrakulikuler' else '-' end as nama_sifat,a.skode as singkatan
            from sis_matpel a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."' $filter ");
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
            'kode_matpel' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'keterangan' => 'required',
            'singkatan' => 'required',
            'sifat' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_matpel,$kode_lokasi,$request->kode_pp)){

                $ins = DB::connection($this->db)->insert("insert into sis_matpel(kode_matpel,nama,kode_lokasi,keterangan,kode_pp,sifat,flag_aktif,tgl_input,skode) values ('$request->kode_matpel','$request->nama','$kode_lokasi','$request->keterangan','$request->kode_pp','$request->sifat','$request->flag_aktif',getdate(),'$request->singkatan')");     

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Mata Pelajaran berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Mata Pelajaran sudah ada di database!";
            }
            
            $success['kode_matpel'] = $request->kode_matpel;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mata Pelajaran gagal disimpan ".$e;
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
            'kode_matpel' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $kode_matpel= $request->kode_matpel;

            $res = DB::connection($this->db)->select("select a.kode_matpel, a.nama,a.keterangan,a.sifat,a.kode_pp,a.flag_aktif,case a.sifat when '0' then 'Nasional' when '1' then 'Muatan Lokal' when '2' then 'Khusus' when '3' then 'Ekstrakulikuler' else '-' end as nama_sifat,b.nama as nama_pp,a.skode as singkatan 
            from sis_matpel a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_matpel='".$kode_matpel."' and a.kode_lokasi='".$kode_lokasi."'  and a.kode_pp='".$kode_pp."'");
            $res = json_decode(json_encode($res),true);
            
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
            'kode_matpel' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'keterangan' => 'required',
            'singkatan' =>'required',
            'sifat' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_matpel')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_matpel', $request->kode_matpel)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into sis_matpel(kode_matpel,nama,kode_lokasi,keterangan,kode_pp,sifat,flag_aktif,tgl_input,skode) values ('$request->kode_matpel','$request->nama','$kode_lokasi','$request->keterangan','$request->kode_pp','$request->sifat','$request->flag_aktif',getdate(),'$request->singkatan')");     
                        
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Mata Pelajaran berhasil diubah";
            $success['kode_matpel'] = $request->kode_matpel;
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mata Pelajaran gagal diubah ".$e;
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
            'kode_matpel' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_matpel')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_matpel', $request->kode_matpel)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Mata Pelajaran berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mata Pelajaran gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
