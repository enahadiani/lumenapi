<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TahunAjaranController extends Controller
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
        
        $auth = DB::connection($this->db)->select("select kode_ta from sis_ta where kode_ta ='".$isi."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'");
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
            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= "and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            if(isset($request->flag_aktif) && $request->flag_aktif != ""){
                $filter .= "and a.flag_aktif='$request->flag_aktif' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_ta) && $request->kode_ta != ""){
                $filter .= "and a.kode_ta='$request->kode_ta' ";
            }else{
                $filter .= "";
            }
            $res = DB::connection($this->db)->select("select a.kode_ta,a.kode_pp+'-'+b.nama as pp,a.nama,convert (varchar, a.tgl_mulai,103) as tgl_mulai,convert (varchar, a.tgl_akhir,103) as tgl_akhir, case when a.flag_aktif='1' then 'AKTIF' else 'NONAKTIF' end as sts,tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status
            from sis_ta a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'	$filter	 
            ");
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
            'kode_ta' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_ta,$kode_lokasi,$request->kode_pp)){
                $ins = DB::connection($this->db)->insert('insert into sis_ta (kode_ta,nama,kode_lokasi,kode_pp,tgl_mulai,tgl_akhir,flag_aktif) values (?, ?, ?, ?, ?, ?, ?)', [$request->kode_ta,$request->nama,$kode_lokasi,$request->kode_pp,$request->tgl_awal,$request->tgl_akhir,$request->flag_aktif]);
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['kode_ta'] = $request->kode_ta;
                $success['message'] = "Data Tahun Ajaran berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode_ta'] = $request->kode_ta;
                $success['jenis'] = 'duplicate';
                $success['message'] = "Error : Duplicate entry. Kode TA sudah ada di database!";
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tahun Ajaran gagal disimpan ".$e;
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
            'kode_ta' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $kode_ta = $request->kode_ta;

            $res = DB::connection($this->db)->select("select kode_pp,kode_ta,nama, flag_aktif,tgl_mulai,tgl_akhir from sis_ta where kode_ta ='".$kode_ta."' and kode_lokasi='".$kode_lokasi."'  and kode_pp='".$kode_pp."'	 
            ");
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
            'kode_ta' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_ta')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_ta', $request->kode_ta)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            $ins = DB::connection($this->db)->insert('insert into sis_ta (kode_ta,nama,kode_lokasi,kode_pp,tgl_mulai,tgl_akhir,flag_aktif) values (?, ?, ?, ?, ?, ?, ?)', [$request->kode_ta,$request->nama,$kode_lokasi,$request->kode_pp,$request->tgl_awal,$request->tgl_akhir,$request->flag_aktif]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode_ta'] = $request->kode_ta;
            $success['message'] = "Data Tahun Ajaran berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['kode_ta'] = $request->kode_ta;
            $success['message'] = "Data Tahun Ajaran gagal diubah ".$e;
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
            'kode_ta' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('sis_ta')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_ta', $request->kode_ta)
            ->where('kode_pp', $request->kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Tahun Ajaran berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tahun Ajaran gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function getPP(Request $request)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_pp)){
                if($request->kode_pp != "" ){

                    $filter = " and kode_pp='$request->kode_pp' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }


            $res = DB::connection($this->db)->select("select kode_pp,nama from pp where kode_lokasi='".$kode_lokasi."' $filter	 
            ");
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

}
