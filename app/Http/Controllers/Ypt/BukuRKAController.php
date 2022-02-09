<?php

namespace App\Http\Controllers\Ypt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class BukuRKAController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter_arr = array($kode_lokasi);
            $filter = "where a.kode_lokasi=? ";
            if(isset($request->no_bukti)){
                if(isset($request->nik_user)){
                    $del =  DB::connection($this->db)->table('dash_buku_dok_tmp')
                    ->where('no_bukti', $request->input('no_bukti'))
                    ->where('nik_user', $request->input('nik_user'))
                    ->where('kode_lokasi', $kode_lokasi)
                    ->delete();
    
                    $ins2 = DB::connection($this->db)->insert("insert into dash_buku_dok_tmp (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis,nik_user) select a.no_bukti,a.kode_lokasi,a.file_dok,a.nama,a.no_urut,a.kode_jenis,? from dash_buku_dok a where a.no_bukti=? and a.kode_lokasi=? ",array($request->input('nik_user'),$request->input('no_bukti'),$kode_lokasi));
                }
                if($request->input('no_bukti') != "all"){
                    $filter .= " and a.no_bukti=? ";
                    array_push($filter_arr,$request->input('no_bukti'));
                }
                $sql= "select a.no_bukti,convert(varchar, a.tanggal, 103) as tanggal,a.keterangan,a.flag_aktif,a.kode_pp,b.nama as nama_pp
                from dash_buku a 
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                $filter  ";
                $filter_arr2 = array($request->input('no_bukti'));
                $sql2 = "select * from dash_buku_dok a where a.no_bukti=? order by a.no_urut ";
            }else{
                $sql = "select a.no_bukti, b.nama as nama_pp, a.flag_aktif,convert(varchar, a.tanggal, 103) as tanggal 
                from dash_buku a
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
                $filter
                ";
            }

            $res = DB::connection($this->db)->select($sql,$filter_arr);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                if(isset($sql2)){
                    $res2 = DB::connection($this->db)->select($sql2,$filter_arr2);
                    $success['data2'] = json_decode(json_encode($res2),true);
                }
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
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
            'tanggal' => 'required',
            'keterangan' => 'required',
            'flag_aktif' => 'required',
            'kode_pp' => 'required',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $this->generateKode("dash_buku", "no_bukti", "BK".substr($request->input('tanggal'),0,2), "001");

            $ins = DB::connection($this->db)->insert("insert into dash_buku(no_bukti,kode_lokasi,tanggal,keterangan,nik_user,tgl_input,flag_aktif,kode_pp) values (?, ?, ?, ?, ?, getdate(), ?, ?)", array($no_bukti,$kode_lokasi,$request->input('tanggal'),$request->input('keterangan'),$nik,$request->input('flag_aktif'),$request->input('kode_pp')));
            
            $get = DB::connection($this->db)->select("select no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis from dash_buku_dok_tmp where nik_user=? and no_bukti=? and kode_lokasi=?",array($request->input('nik_user'),$no_bukti,$kode_lokasi));
            $i=0;
            foreach($get as $row){
                $ins2[] = DB::connection($this->db)->insert("insert into dash_buku_dok (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis) values (?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$row->file_dok,$row->nama,$row->no_urut,$row->kode_jenis));
                if(Storage::disk('s3')->exists('telu/tmp_dok/'.$row->file_dok)){
                    Storage::disk('s3')->move('telu/tmp_dok/'.$row->file_dok, 'telu/'.$row->file_dok);
                }
                $i++;
            }

            // if(isset($request->kode_jenis)){
            //     for($i=0; $i < count($request->kode_jenis); $i++){
            //         $ins3[] = DB::connection($this->db)->insert("insert into dash_buku_dok (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis) values (?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$request->input('nama_file')[$i],$request->input('nama_dok')[$i],$request->input('no_urut')[$i],$request->input('kode_jenis')[$i]));
            //     }
            // }
            
            $del =  DB::connection($this->db)->table('dash_buku_dok_tmp')
            ->where('no_bukti', $no_bukti)
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user',$request->input('nik_user'))
            ->delete();
            
            Storage::disk('s3')->deleteDirectory('telu/tmp_dok');
            Storage::disk('s3')->makeDirectory('telu/tmp_dok');
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Data Buku RKA berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Buku RKA gagal disimpan ".$e;
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
            'no_bukti' => 'required',
            'tanggal' => 'required',
            'keterangan' => 'required',
            'kode_pp' => 'required',
            'flag_aktif' => 'required',
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $update = DB::connection($this->db)->table('dash_buku')
            ->where('kode_lokasi',$kode_lokasi) 
            ->where('no_bukti',$request->input('no_bukti'))
            ->update([
                'keterangan'=>$request->input('keterangan'),
                'kode_pp'=>$request->input('kode_pp'),
                'flag_aktif'=>$request->input('flag_aktif') 
            ]); 
            
            $del2 =  DB::connection($this->db)->table('dash_buku_dok')
            ->where('no_bukti', $request->input('no_bukti'))
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();
            
            $get = DB::connection($this->db)->select("select no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis from dash_buku_dok_tmp where nik_user=? and no_bukti=? and kode_lokasi=?",array($request->input('nik_user'),$request->input('no_bukti'),$kode_lokasi));
            $i=0;
            foreach($get as $row){
                $ins2[] = DB::connection($this->db)->insert("insert into dash_buku_dok (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis) values (?, ?, ?, ?, ?, ?)",array($request->input('no_bukti'),$kode_lokasi,$row->file_dok,$row->nama,$row->no_urut,$row->kode_jenis));
                
                if(Storage::disk('s3')->exists('telu/tmp_dok/'.$row->file_dok)){
                    Storage::disk('s3')->move('telu/tmp_dok/'.$row->file_dok, 'telu/'.$row->file_dok);
                }
                $i++;
            }

            // if(isset($request->kode_jenis)){
            //     for($i=0; $i < count($request->kode_jenis); $i++){
            //         $ins3[] = DB::connection($this->db)->insert("insert into dash_buku_dok (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis) values (?, ?, ?, ?, ?, ?)",array($request->input('no_bukti'),$kode_lokasi,$request->input('nama_file')[$i],$request->input('nama_dok')[$i],$request->input('no_urut')[$i],$request->input('kode_jenis')[$i]));
            //     }
            // }

            $success['kode_jenis'] = $request->kode_jenis;
            $success['nama_file'] = $request->nama_file;
            
            $del =  DB::connection($this->db)->table('dash_buku_dok_tmp')
            ->where('no_bukti',  $request->input('no_bukti'))
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik_user',$request->input('nik_user'))
            ->delete();
            
            Storage::disk('s3')->deleteDirectory('telu/tmp_dok');
            Storage::disk('s3')->makeDirectory('telu/tmp_dok');
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_bukti'] =  $request->input('no_bukti');
            $success['message'] = "Data Buku RKA berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Buku RKA gagal diubah ".$e;
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
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('dash_buku')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $request->no_bukti)->delete();

            $sql = "select file_dok from dash_buku_dok where kode_lokasi=? and no_bukti=?
            ";
            $res = DB::connection($this->db)->select($sql,array($kode_lokasi,$request->input('no_bukti')));
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){
                for($i=0;$i< count($res);$i++){

                    $foto = $res[$i]['file_dok'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('telu/'.$foto);
                    }
                }
            }

            $del2 = DB::connection($this->db)->table('dash_buku_dok')
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            $del3 = DB::connection($this->db)->table('dash_buku_dok_tmp')
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Buku RKA berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Buku RKA gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getJenis(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "where kode_lokasi=? ";
            $filter_arr = array($kode_lokasi);
            if(isset($request->kode_jenis)){
                if($request->input('kode_jenis') != "all"){
                    array_push($filter_arr,$request->input('kode_jenis'));
                    $filter .= " and a.kode_jenis=? ";
                }
            }
            $sql = "select kode_jenis, nama from dash_dok_jenis 
            $filter
            ";

            $res = DB::connection($this->db)->select($sql,$filter_arr);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function storeDokTmp(Request $request){
        $this->validate($request,[
            'file_upload' => 'required',
            'no_urut' => 'required',
            'tanggal' => 'required',
            'nik_user' => 'required',
            'kode_jenis' => 'required',
            'nama' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->beginTransaction();

            if($request->no_bukti == ""){
                $no_bukti = $this->generateKode("dash_buku", "no_bukti", "BK".substr($request->input('tanggal'),0,2), "001");
            }else{
                $no_bukti = $request->no_bukti;
            }
            if($request->hasfile('file_upload')){

                $sql = "select file_dok as file_upload from dash_buku_dok_tmp where no_bukti=? and no_urut=? and nik_user=? and kode_lokasi=?
                ";
                $res = DB::connection($this->db)->select($sql,array($no_bukti,$request->input('no_urut'),$request->input('nik_user'),$kode_lokasi));
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_upload'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('telu/tmp_dok/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('file_upload');
                
                $nama_foto = uniqid()."_".str_replace(' ','_',$file->getClientOriginalName());
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('telu/tmp_dok/'.$foto)){
                    Storage::disk('s3')->delete('telu/tmp_dok/'.$foto);
                }
                Storage::disk('s3')->put('telu/tmp_dok/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }

            $ins2 = DB::connection($this->db)->insert("insert into dash_buku_dok_tmp (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, getdate()) ",array($no_bukti,$kode_lokasi,$foto,$request->input('nama'),$request->input('no_urut'),$request->input('kode_jenis'),$request->input('nik_user')));

            if($ins2){ //mengecek apakah data kosong atau tidak
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['file'] = $foto;
                $success['message'] = "Upload berhasil";
                return response()->json($success, 200);     
            }
            else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['file'] = "-";
                $success['message'] = "Upload gagal";
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

    public function destroyDok(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'no_urut' => 'required'
        ]);
        
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $request->input('no_bukti');
            $no_urut = $request->input('no_urut');

            $cek = DB::connection($this->db)->select("select a.file_dok
            from dash_buku_dok a
            where a.kode_lokasi=? and a.no_bukti=? and a.no_urut=? ",array($kode_lokasi,$no_bukti,$no_urut));
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $file = $cek[0]['file_dok'];
            }else{
                $file = "";
            }

            $del = DB::connection($this->db)->table('dash_buku_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti) 
            ->where('no_urut', $no_urut)
            ->delete();

            $del2 = DB::connection($this->db)->table('dash_buku_dok_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti) 
            ->where('no_urut', $no_urut)
            ->delete();

            if($file != ""){
                if(Storage::disk('s3')->exists('telu/'.$file)){
                    Storage::disk('s3')->delete('telu/'.$file);
                }
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Dokumen Konten berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen Konten gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroyDokTmp(Request $request){
        $this->validate($request,[
            'no_bukti' => 'required',
            'no_urut' => 'required',
            'nik_user' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            DB::connection($this->db)->beginTransaction();
            
            $no_bukti = $request->input('no_bukti');
            $no_urut = $request->input('no_urut');
            $nik_user = $request->input('nik_user');
            $sql = "select file_dok as file_gambar from dash_buku_dok_tmp where no_bukti=? and no_urut=? and nik_user=?
                ";
            $res = DB::connection($this->db)->select($sql,array($no_bukti,$no_urut,$nik_user));
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
                if($foto != ""){
                    if(Storage::disk('s3')->exists('telu/tmp_dok'.$foto)){
                        Storage::disk('s3')->delete('telu/'.$foto);
                    }
                }
            }else{
                $foto = "-";
            }
            
            $upd =  DB::connection($this->db)->table('dash_buku_dok_tmp')
            ->where('nik_user', $nik_user)
            ->where('no_urut', $no_urut)
            ->where('no_bukti', $no_bukti)
            ->update(['file_dok' => '-']);
            
            if($upd){ //mengecek apakah data kosong atau tidak
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Delete berhasil";
                return response()->json($success, 200);     
            }
            else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = "Delete gagal";
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

}
