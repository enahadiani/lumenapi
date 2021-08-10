<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FormatLaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = "sqlsrvrtrw";
    public $guard = "rtrw";

    public function isUnik($isi,$kode_lokasi,$kode_fs){
        
        $auth = DB::connection($this->db)->select("select kode_neraca from neraca where kode_neraca ='".$isi."' and kode_lokasi='".$kode_lokasi."' and kode_fs='".$kode_fs."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
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
            'kode_fs' => 'required',
            'modul' => 'required',
            'kode_neraca' => 'required',
            'nama' =>'required',
            'level_spasi' =>'required',
            'level_lap' =>'required',
            'sum_header' =>'required',
            'jenis_akun' =>'required',
            'kode_induk' =>'required',
            'nu' =>'required',
            'tipe' =>'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $modul = $request->modul;
            $kode_fs = $request->kode_fs;
            $kode_neraca = $request->kode_neraca;

            if($this->isUnik($kode_neraca,$kode_lokasi,$kode_fs)){
                //delete neraca tmp
                $del_tmp = DB::connection($this->db)->table('neraca_tmp')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_fs', $kode_fs)
                ->where('nik_user', $nik)
                ->delete();
    
                //insert neraca tmp
                $nrc_tmp = DB::connection($this->db)->update("insert into neraca_tmp (kode_neraca,kode_fs,nama,level_spasi,level_lap,tipe,sum_header,jenis_akun,kode_induk,rowindex,modul,nik_user,tgl_input,kode_lokasi)
                select kode_neraca,kode_fs,nama,level_spasi,level_lap,tipe,sum_header,jenis_akun,kode_induk,(rowindex*100)+rowindex as rowindex,modul,'$nik' as nik_user,getdate(),kode_lokasi
                from neraca 
                where modul = '$modul' and kode_fs='".$kode_fs."' and kode_lokasi='$kode_lokasi'
                order by rowindex");
    
                //insert 1 row to nrc tmp
                $sql = DB::connection($this->db)->insert("insert into neraca_tmp (kode_neraca,kode_fs,nama,level_spasi,level_lap,tipe,sum_header,jenis_akun,kode_induk,rowindex,modul,nik_user,tgl_input,kode_lokasi) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($kode_neraca,$kode_fs,$request->nama,$request->level_spasi,$request->level_lap,$request->tipe,$request->sum_header,$request->jenis_akun,$request->kode_induk,$request->nu,$request->modul,$nik,date('Y-m-d H:i:s'),$kode_lokasi));
    
                //del nrc
                $del = DB::connection($this->db)->table('neraca')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_fs', $kode_fs)
                ->where('modul', $modul)
                ->delete();
               
                //get nrc dari tmp
                $getnrc = DB::connection($this->db)->select("select kode_neraca,kode_fs,nama,level_spasi,level_lap,tipe,sum_header,jenis_akun,kode_induk,(rowindex*100)+rowindex as rowindex,modul,'$nik' as nik_user,getdate() as tgl_input,kode_lokasi
                from neraca_tmp 
                where modul = '$modul' and kode_fs='$kode_fs' and kode_lokasi='$kode_lokasi'
                order by rowindex");
                $getnrc = json_decode(json_encode($getnrc),true);
                
                //insert nrc
                $i=1;
                for($x=0;$x < count($getnrc);$x++){
                    $ins[$x] =  DB::connection($this->db)->insert("insert into neraca (kode_neraca,kode_fs,nama,level_spasi,level_lap,tipe,sum_header,jenis_akun,kode_induk,rowindex,modul,kode_lokasi) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($getnrc[$x]['kode_neraca'],$getnrc[$x]['kode_fs'],$getnrc[$x]['nama'],$getnrc[$x]['level_spasi'],$getnrc[$x]['level_lap'],$getnrc[$x]['tipe'],$getnrc[$x]['sum_header'],$getnrc[$x]['jenis_akun'],$getnrc[$x]['kode_induk'],$i,$getnrc[$x]['modul'],$kode_lokasi));
                    $i++;
                }
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['kode_neraca'] = $request->kode_neraca;
                $success['message'] = "Data Format Laporan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode_neraca'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Kode Neraca sudah ada di database!";
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Format Laporan gagal disimpan ".$e;
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
            'kode_fs' => 'required',
            'modul' => 'required'
        ]);

        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_fs = $request->kode_fs;
            $modul = $request->modul;
            $res = DB::connection($this->db)->select("select kode_neraca,kode_fs,nama,level_spasi,level_lap,tipe,sum_header,jenis_akun,kode_induk,rowindex,modul,(rowindex*100)+rowindex as nu 
            from neraca 
            where kode_fs='$kode_fs' and kode_lokasi='$kode_lokasi' and modul='$modul'
            order by rowindex				 
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
            'kode_fs' => 'required',
            'modul' => 'required',
            'kode_neraca' => 'required',
            'nama' =>'required',
            'level_spasi' =>'required',
            'level_lap' =>'required',
            'sum_header' =>'required',
            'jenis_akun' =>'required',
            'kode_induk' =>'required',
            'rowindex' => 'required',
            'tipe' =>'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->db)->table('neraca')->where('kode_lokasi', $kode_lokasi)
            ->where('kode_fs', $request->kode_fs)
            ->where('kode_neraca', $request->kode_neraca)
            ->where('modul', $request->modul)
            ->delete();

            $ins = DB::connection($this->db)->insert('insert into neraca (kode_neraca,kode_fs,nama,level_spasi,level_lap,tipe,sum_header,jenis_akun,kode_induk,rowindex,modul,kode_lokasi) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->kode_neraca,$request->kode_fs,$request->nama,$request->level_spasi,$request->level_lap,$request->tipe,$request->sum_header,$request->jenis_akun,$request->kode_induk,$request->rowindex,$request->modul,$kode_lokasi]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['nu'] = $request->nu;
            $success['message'] = "Data Format Laporan berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Format Laporan gagal diubah ".$e;
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
            'kode_fs' => 'required',
            'modul' => 'required',
            'kode_neraca' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $cek = DB::connection($this->db)->select("select kode_neraca,rowindex from neraca where kode_neraca = '".$request->kode_neraca."' and kode_fs='$request->kode_fs' and kode_lokasi='$kode_lokasi' and modul='$request->modul' ");
            $cek = json_decode(json_encode($cek),true);

            if(count($cek) > 0){
                
                $del = DB::connection($this->db)->table('neraca')->where('kode_lokasi', $kode_lokasi)
                ->where('kode_fs', $request->kode_fs)
                ->where('kode_neraca', $request->kode_neraca)
                ->where('modul', $request->modul)
                ->delete();
                $success['status']= true;
                $success['message']= 'Data berhasil dihapus';
            }else{
                $success['status'] = false;
                $success['message']= 'Data tidak ada';
            }
            
            DB::connection($this->db)->commit();            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Format Laporan gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function getVersi()
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_fs,nama from fs where kode_lokasi='$kode_lokasi' 
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
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTipe(Request $request)
    {
        $this->validate($request, [
            'kode_menu' => 'required'
        ]);

        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_fs = $request->kode_fs;
            $modul = $request->modul;
            $res = DB::connection($this->db)->select("select kode_tipe,nama_tipe from tipe_neraca where kode_lokasi='$kode_lokasi' 
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
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function simpanMove(Request $request)
    {
        $this->validate($request, [
            'kode_fs' => 'required',
            'modul' => 'required',
            'kode_neraca' => 'required|array',
            'nama' =>'required|array',
            'level_spasi' =>'required|array',
            'level_lap' =>'required|array',
            'sum_header' =>'required|array',
            'jenis_akun' =>'required|array',
            'kode_induk' =>'required|array',
            'tipe' =>'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $modul = $request->modul;
            $kode_fs = $request->kode_fs;
            $kode_neraca = $request->kode_neraca;
               
            //delete
            $del = DB::connection($this->db)->table('neraca')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_fs', $kode_fs)
            ->where('modul', $modul)
            ->delete();

            $nu=1;
            for($i=0;$i<count($request->kode_neraca);$i++){

                $ins = DB::connection($this->db)->insert("insert into neraca (kode_neraca,kode_fs,nama,level_spasi,level_lap,tipe,sum_header,jenis_akun,kode_induk,rowindex,modul,kode_lokasi) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($request->kode_neraca[$i],$kode_fs,$request->nama[$i],$request->level_spasi[$i],$request->level_lap[$i],$request->tipe[$i],$request->sum_header[$i],$request->jenis_akun[$i],$request->kode_induk[$i],$nu,$request->modul,$kode_lokasi));
                $nu++;
            }
        
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Format Laporan berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Format Laporan gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function getRelakun(Request $request)
    {
        $this->validate($request, [
            'kode_neraca' => 'required',
            'modul' => 'required',
        ]);

        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_neraca = $request->kode_neraca;
            $modul = $request->modul;
            $res = DB::connection($this->db)->select("select kode_akun,nama from masakun where kode_lokasi='$kode_lokasi' and kode_akun not in (select distinct kode_akun from relakun where kode_lokasi='$kode_lokasi' and kode_neraca='$kode_neraca' ) and modul='$modul'
            ") ;
            
            $res = json_decode(json_encode($res),true);

            $sql= "select a.kode_akun,b.nama 
            from relakun a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi' and a.kode_neraca='$kode_neraca' and b.modul='$modul'
            ";
            $res2 = DB::connection($this->db)->select($sql);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['status'] = false;
                $success['data'] = [];
                $success['detail'] = $res2;
                $success['message'] = "Data Tidak ditemukan!";
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function simpanRelasi(Request $request)
    {
        $this->validate($request, [
            'kode_fs' => 'required',
            'kode_neraca' => 'required',
            'kode_akun' => 'array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_fs = $request->kode_fs;
            $kode_neraca = $request->kode_neraca;
               
            //delete
            $del = DB::connection($this->db)->table('relakun')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_fs', $kode_fs)
            ->where('kode_neraca', $kode_neraca)
            ->delete();

            if(isset($request->kode_akun)){

                for($i=0;$i<count($request->kode_akun);$i++){
    
                    $ins = DB::connection($this->db)->insert("insert into relakun (kode_neraca,kode_fs,kode_akun,kode_lokasi) values (?, ?, ?, ?)",array($kode_neraca,$kode_fs,$request->kode_akun[$i],$kode_lokasi));
                }
            }
        
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Relasi Akun Laporan berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Relasi Akun Laporan gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }
}
