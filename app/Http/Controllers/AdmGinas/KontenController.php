<?php

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KontenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'dbsaife';
    public $guard = 'admginas';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->id)){
                if($request->id == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.id='$request->id' ";
                }
                $sql= "select a.id,a.kode_lokasi,convert(varchar,a.tanggal,103) as tanggal,a.judul,a.keterangan,a.nik_user,a.tgl_input,a.flag_aktif,a.header_url,a.kode_klp,a.tag,b.nama as nama_header,c.nama as nama_klp 
                from lab_konten a
                left join lab_konten_galeri b on a.header_url=b.id and a.kode_lokasi=b.kode_lokasi
                left join lab_konten_klp c on a.kode_klp=c.kode_klp
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select a.id,a.kode_lokasi,convert(varchar,a.tanggal,103) as tanggal,a.judul,a.keterangan,a.nik_user,a.flag_aktif,a.header_url,a.kode_klp,a.tag,b.nama as nama_header,c.nama as nama_klp ,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.tgl_input 
                from lab_konten a
                left join lab_konten_galeri b on a.header_url=b.id and a.kode_lokasi=b.kode_lokasi
                left join lab_konten_klp c on a.kode_klp=c.kode_klp
                where a.kode_lokasi='".$kode_lokasi."'
                ";
            }

            $res = DB::connection($this->sql)->select($sql);
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
            'flag_aktif' => 'required',
            'header_url' => 'required',
            'kode_klp' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ins = DB::connection($this->sql)->insert("insert into lab_konten(kode_lokasi,tanggal,judul,keterangan,nik_user,tgl_input,flag_aktif,header_url,kode_klp,tag) values ('$kode_lokasi','$request->tanggal','$request->judul','$request->keterangan','$nik',getdate(),'$request->flag_aktif','$request->header_url','$request->kode_klp','$request->tag') ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->id;
            $success['message'] = "Data Konten berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
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
            'id' => 'required',
            'tanggal' => 'required',
            'judul' => 'required',
            'keterangan' => 'required',
            'flag_aktif' => 'required',
            'header_url' => 'required',
            'kode_klp' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ins = DB::connection($this->sql)->update("update lab_konten set tanggal='$request->tanggal',judul='$request->judul',keterangan='$request->keterangan',header_url='$request->header_url',kode_klp='$request->kode_klp',tag='$request->tag' where id='$request->id' and kode_lokasi='$kode_lokasi' ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->id;
            $success['message'] = "Data Konten berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['kode'] = "-";
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
        $this->validate($request, [
            'id' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('lab_konten')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id', $request->id)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Konten berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getHeader(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->id)){
                if($request->id != "" ){

                    $filter = " and id='$request->id' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql = "SELECT id, nama FROM lab_konten_galeri where kode_lokasi='$kode_lokasi' and (jenis = 'Konten' or kode_klp = 'KLP02') $filter ";

            $res = DB::connection($this->sql)->select($sql);
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

    public function getKlp(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_klp)){
                if($request->kode_klp != "" ){

                    $filter = " and kode_klp='$request->kode_klp' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql = "SELECT kode_klp, nama FROM lab_konten_klp $filter ";

            $res = DB::connection($this->sql)->select($sql);
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

    public function getKategori(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_kategori)){
                if($request->kode_kategori != "" ){

                    $filter = " and kode_kategori='$request->kode_kategori' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql = "SELECT kode_kategori, nama FROM lab_konten_kategori where kode_lokasi='$kode_lokasi' $filter ";

            $res = DB::connection($this->sql)->select($sql);
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

}
