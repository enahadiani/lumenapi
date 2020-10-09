<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class GuruMultiKelasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->kode_pp)){
                $filter = "and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter = "";
            }

            $res = DB::connection('sqlsrvtarbak')->select("select distinct a.nik,a.nama,a.kode_pp+'-'+c.nama as pp,b.tgl_input,case when datediff(minute,b.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,case a.flag_aktif when 1 then 'AKTIF' else 'NONAKTIF' end as flag_aktif,b.kode_matpel+'-'+d.nama as kode_matpel,b.kode_ta,e.nama as nama_ta
            from sis_guru_matpel_kelas b 
            inner join sis_guru a on a.nik=b.nik and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp 
            inner join pp c on a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp  
            inner join sis_matpel d on b.kode_matpel=d.kode_matpel and b.kode_lokasi=d.kode_lokasi and b.kode_pp=d.kode_pp  
            inner join sis_ta e on b.kode_ta=e.kode_ta and b.kode_lokasi=e.kode_lokasi and b.kode_pp=e.kode_pp  
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
            'kode_pp' => 'required',
            'nik_guru' => 'required',
            'flag_aktif' => 'required',
            'kode_matpel' => 'required',
            'kode_ta' => 'required',
            'kode_kelas'=>'required|array'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            date_default_timezone_set('Asia/Jakarta');
            $tgl_input = date('Y-m-d H:i:s');

            if(count($request->kode_kelas) > 0){

                for($i=0;$i<count($request->kode_kelas);$i++){
    
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_guru_matpel_kelas(kode_pp,kode_lokasi,kode_matpel,nik,flag_aktif,kode_kelas,tgl_input,kode_ta) values ( '$request->kode_pp','$kode_lokasi','".$request->kode_matpel."','$request->nik_guru','$request->flag_aktif','".$request->kode_kelas[$i]."','$tgl_input','$request->kode_ta')");
                    
                }
                
            }
            
            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['nik_guru'] = $request->nik_guru;
            $success['message'] = "Data Guru Multi Kelas berhasil disimpan";
            
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Guru Multi Kelas gagal disimpan ".$e;
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
            'nik_guru' => 'required',
            'kode_matpel' => 'required',
            'kode_ta' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $nik_guru= $request->nik_guru;
            $kode_matpel= $request->kode_matpel;
            $kode_ta= $request->kode_ta;

            $res = DB::connection('sqlsrvtarbak')->select("select a.kode_pp, a.nik as nik_guru,a.flag_aktif,a.kode_matpel,b.nama as nama_pp,c.nama as nama_guru, case a.flag_aktif when 1 then 'AKTIF' else 'NONAKTIF' end as nama_status,a.kode_matpel,d.nama as nama_matpel,a.kode_ta,e.nama as nama_ta 
            from sis_guru_matpel_kelas a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            inner join sis_guru c on a.nik=c.nik and a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            inner join sis_matpel d on a.kode_matpel=d.kode_matpel and a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp  
            inner join sis_ta e on a.kode_ta=e.kode_ta and a.kode_lokasi=e.kode_lokasi and a.kode_pp=e.kode_pp  
            where a.nik='$nik_guru' and a.kode_lokasi='".$kode_lokasi."' and a.kode_matpel='".$kode_matpel."' and a.kode_pp='".$kode_pp."'  and a.kode_ta='".$kode_ta."'
            group by a.kode_pp,a.nik,a.flag_aktif,b.nama,c.nama,a.kode_matpel,a.kode_ta,d.nama,e.nama");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection('sqlsrvtarbak')->select("select a.kode_kelas,b.nama as nama_kelas
            from sis_guru_matpel_kelas a 
            inner join sis_kelas b on a.kode_kelas=b.kode_kelas and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.nik='$nik_guru' and a.kode_matpel='".$kode_matpel."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."'  and a.kode_ta='".$kode_ta."'");
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data_detail'] = [];
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
            'kode_pp' => 'required',
            'nik_guru' => 'required',
            'flag_aktif' => 'required',
            'kode_matpel' => 'required',
            'kode_ta' => 'required',
            'kode_kelas'=>'required|array'
        ]);

        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(count($request->kode_kelas) > 0){
                $del = DB::connection('sqlsrvtarbak')->table('sis_guru_matpel_kelas')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('nik', $request->nik_guru)
                ->where('kode_matpel', $request->kode_matpel)
                ->where('kode_pp', $request->kode_pp)
                ->where('kode_ta', $request->kode_ta)
                ->delete();
                
                date_default_timezone_set('Asia/Jakarta');
                $tgl_input = date('Y-m-d H:i:s');

                for($i=0;$i<count($request->kode_kelas);$i++){
    
                    $ins[$i] = DB::connection('sqlsrvtarbak')->insert("insert into sis_guru_matpel_kelas(kode_pp,kode_lokasi,kode_matpel,nik,flag_aktif,kode_kelas,tgl_input,kode_ta) values ( '$request->kode_pp','$kode_lokasi','".$request->kode_matpel."','$request->nik_guru','$request->flag_aktif','".$request->kode_kelas[$i]."','$tgl_input','$request->kode_ta')");
                    
                }
                
            }          
                        
            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['nik_guru'] = $request->nik_guru;
            $success['message'] = "Data Guru Multi Kelas berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Guru Multi Kelas gagal diubah ".$e;
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
            'nik_guru' => 'required',
            'kode_matpel' => 'required',
            'kode_ta' => 'required'
        ]);
        DB::connection('sqlsrvtarbak')->beginTransaction();
        
        try {
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvtarbak')->table('sis_guru_matpel_kelas')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('nik', $request->nik_guru)
                ->where('kode_matpel', $request->kode_matpel)
                ->where('kode_pp', $request->kode_pp)
                ->where('kode_ta', $request->kode_ta)
                ->delete();

            DB::connection('sqlsrvtarbak')->commit();
            $success['status'] = true;
            $success['message'] = "Data Guru Multi Kelas berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvtarbak')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Guru Multi Kelas gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function getNIKGuru(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard('tarbak')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $nik_guru= $request->nik_guru;

            $res = DB::connection('sqlsrvtarbak')->select("select nik, nama from sis_guru where kode_lokasi = '".$kode_lokasi."' and kode_pp='".$kode_pp."' ");
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
