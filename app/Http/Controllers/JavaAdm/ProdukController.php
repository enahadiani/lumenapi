<?php
namespace App\Http\Controllers\JavaAdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProdukController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
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

            if(isset($request->kode_produk)){
                if($request->kode_produk == "all"){
                    $filter = "";
                }else{
                    $filter = " and id_produk='$request->kode_produk' ";
                }
                $sql= "select id_produk, nama_produk, keterangan
                from javaadmin_produk where kode_lokasi='".$kode_lokasi."' $filter ";

                $file = "select a.path_foto, a.nama_foto
                from javaadmin_produk_foto a 
                inner join javaadmin_produk b on a.id_produk=b.id_produk and a.kode_lokasi=b.kode_lokasi
                where b.id_produk = '$request->kode_produk'";
                $file = DB::connection($this->sql)->select($file);
                $file = json_decode(json_encode($file),true);
                $success['file'] = $file;

            }else{
                $sql = "select id_produk, nama_produk,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status 
                from javaadmin_produk
                where kode_lokasi= '$kode_lokasi'";
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'nama_produk' => 'required',
            'keterangan' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $id_produk = $this->generateKode('javaadmin_produk', 'id_produk', $kode_lokasi."-PRD".".", '00001');
            
            $insert= "insert into javaadmin_produk(id_produk, nama_produk, keterangan, kode_lokasi, tgl_input)
            values('".$id_produk."', '".$request->nama_produk."', '".$request->keterangan."', '$kode_lokasi', getdate())";
                
            DB::connection($this->sql)->insert($insert);

            $arr_foto = array();
            $arr_nama_foto = array();
            $cek = $request->file;

            if(!empty($cek)) {
                if(count($request->file) > 0) {
                    for($i=0;$i<count($request->nama_foto);$i++){ 
                        if(isset($request->file('file')[$i])){ 
                            $file = $request->file('file')[$i];
                            $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $foto = $nama_foto;
                            Storage::disk('s3')->put('webjava/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                            $arr_nama_foto[] = $request->nama_foto[$i];
                        }
                    }
                }
                if(count($arr_nama_foto) > 0){
                    for($i=0; $i<count($arr_nama_foto);$i++){
                        $insertFile = "insert into javaadmin_produk_foto(id_produk, kode_lokasi, path_foto, nama_foto)
                        values ('".$id_produk."', '".$kode_lokasi."', '".$arr_foto[$i]."', '".$arr_nama_foto[$i]."')";
                        DB::connection($this->sql)->insert($insertFile); 
                    }
                }
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $id_produk;
            $success['message'] = "Data Produk berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Produk gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id_produk' => 'required',
            'nama_produk' => 'required',
            'keterangan' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->sql)->table('javaadmin_produk')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id_produk', $request->id_produk)
            ->delete();

            $insert= "insert into javaadmin_produk(id_produk, nama_produk, keterangan, kode_lokasi, tgl_input)
            values('".$request->id_produk."', '".$request->nama_produk."', '".$request->keterangan."', '$kode_lokasi', getdate())";
                
            DB::connection($this->sql)->insert($insert);
            $arr_foto = array();
            $arr_nama_foto = array();
            $cek = $request->file;

            if(!empty($cek)) {
                if(count($request->file) > 0) { 
                    for($i=0;$i<count($request->nama_foto);$i++){
                        if(isset($request->file('file')[$i])){  
                            $file = $request->file('file')[$i];
                            $fileName = $file->getClientOriginalName();
                            if($request->nama_file_seb[$i] != "-"){
                                //kalo ada hapus yang lama
                                Storage::disk('s3')->delete('webjava/'.$request->nama_file_seb[$i]);
                            }
                            if($fileName == 'empty.jpg') {
                                $arr_foto[] = $request->nama_file_seb[$i];
                                $arr_nama_foto[] = $request->nama_foto[$i];
                            } else {
                                $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                $foto = $nama_foto;
                                if(Storage::disk('s3')->exists('webjava/'.$foto)){
                                    Storage::disk('s3')->delete('webjava/'.$foto);
                                }
                                Storage::disk('s3')->put('webjava/'.$foto,file_get_contents($file));
                                $arr_foto[] = $foto;
                                $arr_nama_foto[] = $foto;
                            }
                        }
                    }
                    DB::connection($this->sql)->table('javaadmin_produk_foto')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_produk', $request->id_produk)
                    ->delete();
                    
                    if(count($arr_nama_foto) > 0){
                        for($i=0; $i<count($arr_nama_foto);$i++){
                            $insertFile = "insert into javaadmin_produk_foto(id_produk, kode_lokasi, path_foto, nama_foto)
                            values ('".$request->id_produk."', '$kode_lokasi', '".$arr_foto[$i]."','".$arr_nama_foto[$i]."')";
                            DB::connection($this->sql)->insert($insertFile); 
                        }
                    }
                }
            }
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->id_produk;
            $success['message'] = "Data Produk berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Produk gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'id_produk' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select path_foto from javaadmin_produk_foto where kode_lokasi='".$kode_lokasi."' and id_produk='".$request->id_produk."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){
                for($i=0;$i<count($res);$i++) {
                    $foto = $res[$i]['path_foto'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('webjava/'.$foto);
                    }
                }
            }

            DB::connection($this->sql)->table('javaadmin_produk_foto')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id_produk', $request->id_produk)
            ->delete();
            
            DB::connection($this->sql)->table('javaadmin_produk')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id_produk', $request->id_produk)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Produk berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Produk gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}

?>