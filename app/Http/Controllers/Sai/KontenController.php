<?php

namespace App\Http\Controllers\Sai;

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
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_barang from brg_barang where kode_barang ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            if(isset($request->id)){
                if($request->id == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and no_konten='$request->id' ";
                }  
                $sql = "select no_konten, convert(varchar, tanggal, 105) as tanggal, judul, isi, file_gambar,kode_kategori,tag,jenis,tipe from sai_konten where kode_lokasi='".$kode_lokasi."'  $filter ";
            }else{
                $sql = "select convert(int, no_konten) as no_konten, judul, flag_aktif, jenis, convert(varchar, tanggal, 106) as tanggal from sai_konten 
                where kode_lokasi= '".$kode_lokasi."' ";
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
     * Show the from for creating a new resource.
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
            'isi' => 'required',
            'tag' => 'required',
            'kode_kategori' => 'required',
            'gambar' => 'required',
            'jenis' => 'required',
            'tipe' => 'required',
            'status_simpan' => 'required|in:langsung,konten'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->status_simpan == 'langsung'){
                $tgl = date('Y-m-d H:i:s');
                $flag_aktif = "1";
            }else{
                $tgl = date('m-d-Y H:i:s');
                $flag_aktif = "0";
            }

            $req = $request->all();

            $sql = " select isnull(max(convert(int,no_konten)),0)+1 as id from sai_konten where kode_lokasi='$kode_lokasi' ";
            $rs1 = DB::connection($this->sql)->select($sql);

            $id = $rs1[0]->id;

            $ins = DB::connection($this->sql)->insert("insert into sai_konten (no_konten,kode_lokasi,tanggal,judul,isi,nik_buat,tgl_input,flag_aktif,tag,kode_kategori,file_gambar,jenis,tipe) values ('$id','".$kode_lokasi."','$tgl','".$req['judul']."','".$req['isi']."','".$nik."','".$req['tanggal']."','$flag_aktif','".$req['tag']."','".$req['kode_kategori']."','".$req['gambar']."','".$req['jenis']."','".$req['tipe']."') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
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
     * Show the from for editing the specified resource.
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
            'judul' => 'required',
            'isi' => 'required',
            'gambar' => 'required',
            'kode_kategori' => 'required',
            'tag' => 'required',
            'jenis' => 'required',
            'tipe' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $date = date('m-d-Y H:i:s');
            $req = $request->all();
            $ins = DB::connection($this->sql)->update("update sai_konten set judul='".$req['judul']."',tanggal='".$date."',isi='".$req['isi']."',file_gambar='".$req['gambar']."',kode_kategori='".$req['kode_kategori']."',tag='".$req['tag']."',jenis='".$req['jenis']."',flag_aktif='1',tipe='".$req['tipe']."' where no_konten = '".$req['id']."' and kode_lokasi='".$kode_lokasi."'");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Konten berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function updateDraft(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'judul' => 'required',
            'isi' => 'required',
            'gambar' => 'required',
            'kode_kategori' => 'required',
            'tag' => 'required',
            'jenis' => 'required',
            'tipe' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $date = date('m-d-Y H:i:s');
            $req = $request->all();
            $ins = DB::connection($this->sql)->update("update sai_konten set judul='".$req['judul']."',tanggal='".$date."',isi='".$req['isi']."',file_gambar='".$req['gambar']."',kode_kategori='".$req['kode_kategori']."',tag='".$req['tag']."',jenis='".$req['jenis']."',flag_aktif='0',tipe='".$req['tipe']."' where no_konten = '".$req['id']."' and kode_lokasi='".$kode_lokasi."'");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Draf Konten berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Draf Konten gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function draftKonten(Request $request)
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

            $date = date('m-d-Y H:i:s');
            $req = $request->all();
            $ins = DB::connection($this->sql)->update("update sai_konten set flag_aktif='0' where no_konten = '".$req['id']."' and kode_lokasi='".$kode_lokasi."'");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Draf Konten berhasil disimpan";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Draf Konten gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function publishKonten(Request $request)
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

            $date = date('m-d-Y H:i:s');
            $req = $request->all();
            $ins = DB::connection($this->sql)->update("update sai_konten set flag_aktif='1' where no_konten = '".$req['id']."' and kode_lokasi='".$kode_lokasi."'");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Konten berhasil dipublish";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten gagal dipublish ".$e;
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
           
            $del = DB::connection($this->sql)->table('sai_konten')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_konten', $request->id)
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

}
