<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KelasKhususController extends Controller
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
        
        $auth = DB::connection($this->db)->select("select kode_kelas from sis_kelas_khusus where kode_kelas ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'");
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
            
            if(isset($request->kode_kelas)){
                $filter .= "and a.kode_kelas='$request->kode_kelas' ";
            }else{
                $filter .= "";
            }

            if(isset($request->flag_aktif)){
                $filter .= "and a.flag_aktif='$request->flag_aktif' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select( "select a.kode_kelas,a.nama,a.kode_tingkat,a.kode_jur,a.kode_pp+'-'+c.nama as pp,case a.flag_aktif when 1 then 'AKTIF' else 'NONAKTIF' end as flag_aktif,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, isnull(dbo.fnGetMatpelKhusus(a.kode_kelas,a.kode_lokasi,a.kode_pp),'-') as matpel 
            from sis_kelas_khusus a 
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_pp=c.kode_pp  and a.kode_lokasi=c.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' $filter ");
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
            'kode_kelas' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'kode_tingkat' => 'required',
            'kode_jur' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_kelas,$kode_lokasi,$request->kode_pp)){

                $ins = DB::connection($this->db)->insert("insert into sis_kelas_khusus(kode_kelas,nama,kode_tingkat,kode_jur,flag_aktif,kode_lokasi,kode_pp,tgl_input) values ('$request->kode_kelas','$request->nama','$request->kode_tingkat','$request->kode_jur','$request->flag_aktif','$kode_lokasi','$request->kode_pp',getdate())");
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['kode'] = $request->kode_kelas;
                $success['message'] = "Data Kelas Khusus berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = $request->kode_kelas;
                $success['jenis'] = 'duplicate';
                $success['message'] = "Error : Duplicate entry. Kode Kelas Khusus sudah ada di database!";
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelas Khusus gagal disimpan ".$e;
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
            'kode_kelas' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $kode_kelas = $request->kode_kelas;

            $res = DB::connection($this->db)->select("select a.nama, a.kode_kelas,a.kode_jur,a.kode_tingkat,a.kode_kelas,a.flag_aktif,a.kode_pp,b.nama as nama_pp,c.nama as nama_jur,d.nama as nama_tingkat 
            from sis_kelas_khusus a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join sis_jur c on a.kode_jur=c.kode_jur and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            inner join sis_tingkat d on a.kode_tingkat=d.kode_tingkat and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp where a.kode_kelas ='".$kode_kelas."' and a.kode_lokasi='".$kode_lokasi."'  and a.kode_pp='".$kode_pp."' ");
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
            'kode_kelas' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'kode_tingkat' => 'required',
            'kode_jur' => 'required',
            'flag_aktif'=> 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_kelas_khusus')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_kelas', $request->kode_kelas)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into sis_kelas_khusus(kode_kelas,nama,kode_tingkat,kode_jur,flag_aktif,kode_lokasi,kode_pp,tgl_input) values ('$request->kode_kelas','$request->nama','$request->kode_tingkat','$request->kode_jur','$request->flag_aktif','$kode_lokasi','$request->kode_pp',getdate())");
                        
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_kelas;
            $success['message'] = "Data Kelas Khusus berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelas Khusus gagal diubah ".$e;
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
            'kode_kelas' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_kelas_khusus')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_kelas', $request->kode_kelas)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kelas Khusus berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelas Khusus gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
