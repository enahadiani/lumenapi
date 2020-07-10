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

            $sql = "select convert(int, no_konten) as no_konten, judul, flag_aktif, jenis, convert(varchar, tanggal, 106) as tanggal from sai_konten 
            where kode_lokasi= '".$kode_lokasi."' ";

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
            'nik_buat' => 'required',
            'tgl_input' => 'required',
            'flag_aktif' => 'required',
            'tag' => 'required',
            'kode_kategori' => 'required',
            'file_gambar' => 'required',
            'jenis' => 'required',
            'tipe' => 'required',
            'status_simpan' => 'required|in:langsung,tidak'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->status_simpan == 'langsung'){
                $tgl = date('Y-m-d H:i:s');
            }else{
                $tgl = date('m-d-Y H:i:s');
            }

            $req = $request->all();

            $ins = DB::connection($this->sql)->insert("insert into sai_konten (no_konten,kode_lokasi,tanggal,judul,isi,nik_buat,tgl_input,flag_aktif,tag,kode_kategori,file_gambar,jenis,tipe) values ('$id','".$req['kode_lokasi']."','$tgl','".$req['judul']."','".$req['isi']."','".$req['nik_user']."','".$req['tanggal']."','1','".$req['tag']."','".$req['kode_kategori']."','".$req['file_gambar']."','".$req['jenis']."','".$req['tipe']."') ");

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
            'kode_barang' => 'required',
            'nama' => 'required',
            'sat_kecil' => 'required',
            'sat_besar' => 'required',
            'jml_sat' => 'required',
            'hna' => 'required',
            'pabrik' => 'required',
            'flag_aktif' => 'required',
            'ss' => 'required',
            'sm1' => 'required',
            'sm2' => 'required',
            'mm1' => 'required',
            'mm2' => 'required',
            'fm1' => 'required',
            'fm2' => 'required',
            'kode_klp' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'barcode' => 'required',
            'hrg_satuan' => 'required',
            'ppn' => 'required',
            'profit' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select file_gambar from brg_barang where kode_lokasi='".$kode_lokasi."' and kode_barang='$request->kode_barang' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
            }else{
                $foto = "-";
            }
            
            if($request->hasfile('foto')){
                if($foto != "" || $foto != "-"){
                    Storage::disk('s3')->delete('toko/'.$foto);
                }
                
                $file = $request->file('foto');
                
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('toko/'.$foto)){
                    Storage::disk('s3')->delete('toko/'.$foto);
                }
                Storage::disk('s3')->put('toko/'.$foto,file_get_contents($file));
                
            }
            
            $del = DB::connection($this->sql)->table('brg_barang')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_barang', $request->kode_barang)
            ->delete();

            $ins = DB::connection($this->sql)->insert('insert into brg_barang(kode_barang,nama,kode_lokasi,sat_kecil,sat_besar,jml_sat,hna,pabrik,flag_gen,flag_aktif,ss,sm1,sm2,mm1,mm2,fm1,fm2,kode_klp,file_gambar,barcode,hrg_satuan,ppn,profit) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->kode_barang,$request->nama,$kode_lokasi,$request->sat_kecil, $request->sat_besar, $request->jml_sat,$request->hna,$request->pabrik,$request->flag_gen, $request->flag_aktif,$request->ss, $request->sm1, $request->sm2, $request->mm1, $request->mm2, $request->fm1, $request->fm2, $request->kode_klp, $foto, $request->barcode,$request->hrg_satuan, $request->ppn, $request->profit));
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Barang berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barang gagal diubah ".$e;
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
            'kode_barang' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select file_gambar from brg_barang where kode_lokasi='".$kode_lokasi."' and kode_barang='$request->kode_barang' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
                if($foto != ""){
                    Storage::disk('s3')->delete('toko/'.$foto);
                }
            }

            $del = DB::connection($this->sql)->table('brg_barang')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_barang', $request->kode_barang)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Barang berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barang gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
