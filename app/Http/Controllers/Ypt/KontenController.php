<?php

namespace App\Http\Controllers\Ypt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KontenController extends Controller
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

            if(isset($request->no_konten)){
                if(isset($request->nik_user)){
                    $del =  DB::connection($this->db)->table('dash_konten_dok_tmp')
                    ->where('no_bukti', $request->no_konten)
                    ->where('kode_lokasi', $kode_lokasi)
                    ->delete();
    
                    $ins2 = DB::connection($this->db)->insert("insert into dash_konten_dok_tmp (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis,nik_user) select a.no_bukti,a.kode_lokasi,a.file_dok,a.nama,a.no_urut,a.kode_jenis,'$request->nik_user' from dash_konten_dok a where a.no_bukti='$request->no_konten' and a.kode_lokasi='$kode_lokasi' and a.kode_jenis <> 'DK03' ");
                }
                if($request->no_konten == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_konten='$request->no_konten' ";
                }
                $sql= "select a.no_konten, convert(varchar, a.tanggal, 103) as tanggal, a.judul, a.isi as keterangan, a.file_gambar,a.kode_kategori,a.tag,a.flag_aktif,b.nama as nama_kategori from dash_konten a 
                inner join dash_konten_ktg b on a.kode_kategori=b.kode_ktg and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."'  $filter  ";

                $sql2 = "select * from dash_konten_dok a where a.no_bukti='$request->no_konten' order by a.no_urut ";
            }else{
                $sql = "select no_konten, judul, flag_aktif,convert(varchar, tanggal, 103) as tanggal from dash_konten 
                where kode_lokasi='".$kode_lokasi."'
                ";
            }

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                if(isset($sql2)){
                    $res2 = DB::connection($this->db)->select($sql2);
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
            'judul' => 'required',
            'keterangan' => 'required',
            'tag' => 'required',
            'kode_kategori' => 'required',
            'flag_aktif' => 'required',
            'file_gambar' => 'file|mimes:jpeg,png,jpg,mp4,avi,svg,webp'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $file_type = $file->getmimeType();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('telu/'.$foto)){
                    Storage::disk('s3')->delete('telu/'.$foto);
                }
                Storage::disk('s3')->put('telu/'.$foto,file_get_contents($file));
                $foto = $foto;
            }else{

                $foto="-";
                $file_type = "-";
            }

            $no_bukti = $this->generateKode("dash_konten", "no_konten", "KTN".substr($request->tanggal,0,2), "001");

            $ins = DB::connection($this->db)->insert("insert into dash_konten(no_konten,kode_lokasi,tanggal,judul,isi,nik_buat,tgl_input,flag_aktif,tag,kode_kategori,file_gambar) values ('".$no_bukti."','".$kode_lokasi."','".$request->tanggal."','".$request->judul."','".$request->keterangan."','$nik',getdate(),'".$request->flag_aktif."','$request->tag','$request->kode_kategori','$foto') ");
            
            $get = DB::connection($this->db)->select("select no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis from dash_konten_dok_tmp where nik_user='$request->nik_user' and no_bukti='$no_bukti' ");
            $i=0;
            foreach($get as $row){
                
                $ins2[] = DB::connection($this->db)->insert("insert into dash_konten_dok (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis) values ('$no_bukti','$kode_lokasi','$row->file_dok','$row->nama','$row->no_urut','$row->kode_jenis') ");
                
                if(Storage::disk('s3')->exists('telu/tmp_dok/'.$row->file_dok)){
                    Storage::disk('s3')->move('telu/tmp_dok/'.$row->file_dok, 'telu/'.$row->file_dok);
                }
                $i++;
            }

            if(isset($request->kode_jenis)){
                for($i=0; $i < count($request->kode_jenis); $i++){
                    if($request->kode_jenis[$i] == "DK03"){
                        $ins3[] = DB::connection($this->db)->insert("insert into dash_konten_dok (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis) values ('$no_bukti','$kode_lokasi','".$request->nama_file[$i]."','".$request->nama_dok[$i]."','".$request->no_urut[$i]."','".$request->kode_jenis[$i]."') ");
                    }
                
                }
            }
            
            $del =  DB::connection($this->db)->table('dash_konten_dok_tmp')
            ->where('no_bukti', $no_bukti)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();
            
            Storage::disk('s3')->deleteDirectory('telu/tmp_dok');
            Storage::disk('s3')->makeDirectory('telu/tmp_dok');
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_konten'] = $no_bukti;
            $success['message'] = "Data Konten berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten gagal disimpan ".$e;
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
            'no_konten' => 'required',
            'tanggal' => 'required',
            'judul' => 'required',
            'keterangan' => 'required',
            'tag' => 'required',
            'kode_kategori' => 'required',
            'flag_aktif' => 'required',
            'file_gambar' => 'file|mimes:jpeg,png,jpg,mp4,avi,svg,webp'
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select file_gambar from dash_konten where kode_lokasi='".$kode_lokasi."' and no_konten='$request->no_konten' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
            }else{
                $foto = "-";
            }

            if($request->hasfile('file_gambar')){

                if(count($res) > 0){
                    if($res[0]['file_gambar'] != "-"){
    
                        $foto = $res[0]['file_gambar'];
                        if($foto != ""){
                            Storage::disk('s3')->delete('telu/'.$foto);
                        }
                    }
                }else{
                    $foto = "-";
                }
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('telu/'.$foto)){
                    Storage::disk('s3')->delete('telu/'.$foto);
                }
                Storage::disk('s3')->put('telu/'.$foto,file_get_contents($file));
                
                $file_type = $file->getmimeType();
            }else{

                $file_type = "-";
            }
            
            $update = DB::connection($this->db)->update("update dash_konten set judul='$request->judul',isi='$request->keterangan',file_gambar='$foto',tag='$request->tag',kode_kategori='$request->kode_kategori',flag_aktif='$request->flag_aktif' where kode_lokasi='$kode_lokasi' and no_konten='$request->no_konten' ");
            
            $del2 =  DB::connection($this->db)->table('dash_konten_dok')
            ->where('no_bukti', $request->no_konten)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();
            
            $get = DB::connection($this->db)->select("select no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis from dash_konten_dok_tmp where nik_user='$request->nik_user' and no_bukti='$request->no_konten' ");
            $i=0;
            $success['get'] = "select no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis from dash_konten_dok_tmp where nik_user='$request->nik_user' and no_bukti='$request->no_konten' ";
            foreach($get as $row){
                
                $ins2[] = DB::connection($this->db)->insert("insert into dash_konten_dok (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis) values ('$request->no_konten','$kode_lokasi','$row->file_dok','$row->nama','$row->no_urut','$row->kode_jenis') ");
                
                if(Storage::disk('s3')->exists('telu/tmp_dok/'.$row->file_dok)){
                    Storage::disk('s3')->move('telu/tmp_dok/'.$row->file_dok, 'telu/'.$row->file_dok);
                }
                $i++;
            }

            if(isset($request->kode_jenis)){
                for($i=0; $i < count($request->kode_jenis); $i++){
                    if($request->kode_jenis[$i] == "DK03"){
                        $ins3[] = DB::connection($this->db)->insert("insert into dash_konten_dok (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis) values ('$request->no_konten','$kode_lokasi','".$request->nama_file[$i]."','".$request->nama_dok[$i]."','".$request->no_urut[$i]."','".$request->kode_jenis[$i]."') ");
                    }
                
                }
            }

            $success['kode_jenis'] = $request->kode_jenis;
            $success['nama_file'] = $request->nama_file;
            
            $del =  DB::connection($this->db)->table('dash_konten_dok_tmp')
            ->where('no_bukti', $request->no_konten)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();
            
            Storage::disk('s3')->deleteDirectory('telu/tmp_dok');
            Storage::disk('s3')->makeDirectory('telu/tmp_dok');
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_konten'] = $request->no_konten;
            $success['message'] = "Data Konten berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten gagal diubah ".$e;
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
            'no_konten' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql3="select file_gambar from dash_konten where kode_lokasi='".$kode_lokasi."' and no_konten='$request->no_konten'";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){
                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('telu/'.$res3[$i]['file_gambar']);
                }
            }
            
            $del = DB::connection($this->db)->table('dash_konten')->where('kode_lokasi', $kode_lokasi)->where('no_konten', $request->no_konten)->delete();

            $sql = "select file_dok from dash_konten_dok where no_bukti='$request->no_konten' and kode_lokasi='$kode_lokasi'
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){
                for($i=0;$i< count($res);$i++){

                    $foto = $res[$i]['file_dok'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('telu/'.$foto);
                    }
                }
            }

            $del2 = DB::connection($this->db)->table('dash_konten_dok')
            ->where('no_bukti', $request->no_konten)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            $del3 = DB::connection($this->db)->table('dash_konten_dok_tmp')
            ->where('no_bukti', $request->no_konten)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Konten berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getKonten(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_ktg)){
                if($request->kode_ktg == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_ktg='$request->kode_ktg' ";
                }
                $sql= "select kode_ktg, nama from dash_konten_ktg where kode_lokasi='".$kode_lokasi."'  $filter ";
            }else{
                $sql = "select kode_ktg, nama from dash_konten_ktg 
                where kode_lokasi='".$kode_lokasi."'
                ";
            }

            $res = DB::connection($this->db)->select($sql);
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

    public function getJenis(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_jenis)){
                if($request->kode_jenis == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_jenis='$request->kode_jenis' ";
                }
                $sql= "select kode_jenis, nama from dash_dok_jenis where kode_lokasi='".$kode_lokasi."'  $filter ";
            }else{
                $sql = "select kode_jenis, nama from dash_dok_jenis 
                where kode_lokasi='".$kode_lokasi."'
                ";
            }

            $res = DB::connection($this->db)->select($sql);
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
                $no_konten = $this->generateKode("dash_konten", "no_konten", "KTN".substr($request->tanggal,0,2), "001");
            }else{
                $no_konten = $request->no_bukti;
            }
            if($request->hasfile('file_upload')){

                $sql = "select file_dok as file_upload from dash_konten_dok_tmp where no_bukti='$no_konten' and no_urut='$request->no_urut' and nik_user='$request->nik_user'
                ";
                $res = DB::connection($this->db)->select($sql);
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

            $ins2 = DB::connection($this->db)->insert("insert into dash_konten_dok_tmp (no_bukti,kode_lokasi,file_dok,nama,no_urut,kode_jenis,nik_user) values ('$no_konten','$kode_lokasi','$foto','$request->nama','$request->no_urut','$request->kode_jenis','$request->nik_user') ");

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
            $no_bukti = $request->no_bukti;
            $no_urut = $request->no_urut;

            $cek = DB::connection($this->db)->select("select a.file_dok
            from dash_konten_dok a
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='".$no_bukti."' and a.no_urut='".$no_urut."' ");
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $file = $cek[0]['file_dok'];
            }else{
                $file = "";
            }

            $del = DB::connection($this->db)->table('dash_konten_dok')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti) 
            ->where('no_urut', $no_urut)
            ->delete();

            $del2 = DB::connection($this->db)->table('dash_konten_dok_tmp')
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

            $sql = "select file_dok as file_gambar from dash_konten_dok_tmp where no_bukti='$request->no_bukti' and no_urut='$request->no_urut' and nik_user='$request->nik_user'
                ";
            $res = DB::connection($this->db)->select($sql);
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
            
            $upd =  DB::connection($this->db)->table('dash_konten_dok_tmp')
            ->where('nik_user', $request->nik_user)
            ->where('no_urut', $request->no_urut)
            ->where('no_bukti', $request->no_bukti)
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
