<?php

namespace App\Http\Controllers\Gl;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MasakunController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function index()
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $akun = DB::connection('sqlsrv2')->select("select kode_akun,kode_lokasi,nama,modul,jenis,kode_curr,block,status_gar,normal from masakun where kode_lokasi='$kode_lokasi'		 
            ");
            $akun = json_decode(json_encode($akun),true);
            
            if(count($akun) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $akun;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
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
            'kode_akun' => 'required',
            'nama' => 'required',
            'modul' => 'required',
            'jenis' => 'required',
            'kode_curr' => 'required',
            'block' => 'required',
            'status_gar' => 'required',
            'normal' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }
            
            $ins = DB::connection('sqlsrv2')->insert('insert into masakun (kode_akun,kode_lokasi,nama,modul,jenis,kode_curr,block,status_gar,normal) values  (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('kode_akun'),$kode_lokasi,$request->input('nama'),$request->input('jenis'),$request->input('kode_curr'),$request->input('block'),$request->input('status_gar'),$request->input('normal')]);
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Master akun berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Master akun gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show($kode_akun)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $akun = DB::connection('sqlsrv2')->select("select kode_akun,kode_lokasi,nama,modul,jenis,kode_curr,block,status_gar,normal from masakun where kode_lokasi='$kode_lokasi' and kode_akun='$kode_akun'				 
            ");

            $akun = json_decode(json_encode($akun),true);

            $akun2 = DB::connection('sqlsrv2')->select("select b.kode_flag,b.nama from flag_relasi a inner join flag_akun b on a.kode_flag=b.kode_flag where a.kode_akun = '".$kode_akun."' and a.kode_lokasi='".$kode_lokasi."'
            ");

            $akun2 = json_decode(json_encode($akun2),true);

            $akun3 = DB::connection('sqlsrv2')->select("select b.kode_fs,b.nama as nama_fs,c.kode_neraca,c.nama as nama_lap 
            from relakun a 
            inner join fs b on a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi 
            inner join neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi 
            where a.kode_akun = '".$kode_akun."' and a.kode_lokasi='".$kode_lokasi."'
            ");

            $akun3 = json_decode(json_encode($akun3),true);	
            
            $akun4 = DB::connection('sqlsrv2')->select("select b.kode_fs,b.nama as nama_fs,c.kode_neraca,c.nama as nama_lap 
            from relakungar a 
            inner join fsgar b on a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi 
            inner join neracagar c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi 
            where a.kode_akun = '".$kode_akun."' and a.kode_lokasi='".$kode_lokasi."'
            ");

            $akun4 = json_decode(json_encode($akun4),true);	
            
            if(count($akun) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $akun;
                $success['detail_relasi'] = $akun2;
                $success['detail_keuangan'] = $akun3;
                $success['detail_anggaran'] = $akun4;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
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
    public function update(Request $request, $kode_fs)
    {
        $this->validate($request, [
            'nama' => 'required',
            'modul' => 'required',
            'jenis' => 'required',
            'kode_curr' => 'required',
            'block' => 'required',
            'status_gar' => 'required',
            'normal' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }
            
            $del = DB::connection('sqlsrv2')->table('masakun')->where('kode_lokasi', $kode_lokasi)->where('kode_akun', $kode_akun)->delete();

            $ins = DB::connection('sqlsrv2')->insert('insert into masakun (kode_akun,kode_lokasi,nama,modul,jenis,kode_curr,block,status_gar,normal) values  (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('kode_akun'),$kode_lokasi,$request->input('nama'),$request->input('jenis'),$request->input('kode_curr'),$request->input('block'),$request->input('status_gar'),$request->input('normal')]);
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Master Akun berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Master Akun gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($kode_akun)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }
            
            $del = DB::connection('sqlsrv2')->table('masakun')->where('kode_lokasi', $kode_lokasi)->where('kode_akun', $kode_akun)->delete();

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Masakun berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Masakun gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function getCurrency()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $curr = DB::connection('sqlsrv2')->select("select kode_curr from curr		 
            ");
            $curr = json_decode(json_encode($curr),true);
            
            if(count($curr) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $curr;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getModul()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $modul = DB::connection('sqlsrv2')->select("select kode_tipe,nama_tipe from tipe_neraca where kode_lokasi='$kode_lokasi'	 
            ");
            $modul = json_decode(json_encode($modul),true);
            
            if(count($modul) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $modul;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getFlagAkun()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $modul = DB::connection('sqlsrv2')->select("select kode_flag, nama from flag_akun
            ");
            $modul = json_decode(json_encode($modul),true);
            
            if(count($modul) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $modul;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    
    public function getNeraca($kode_fs)
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $res = DB::connection('sqlsrv2')->select("select kode_neraca, nama from neraca where kode_fs='".$kode_fs."' and tipe = 'posting' and kode_lokasi='".$kode_lokasi."'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    
    public function getFSGar()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $res = DB::connection('sqlsrv2')->select("select kode_fs, nama from fsgar where kode_lokasi='$kode_lokasi' ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    
    public function getNeracaGar($kode_fs)
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $modul = DB::connection('sqlsrv2')->select("select kode_neraca, nama from neracagar where kode_fs='".$kode_fs."' and tipe = 'posting' and kode_lokasi='".$kode_lokasi."'
            ");
            $modul = json_decode(json_encode($modul),true);
            
            if(count($modul) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $modul;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}
