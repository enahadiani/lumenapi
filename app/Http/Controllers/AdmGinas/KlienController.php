<?php
namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KlienController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'dbsaife';
    public $guard = 'admginas';

    public function index(Request $request) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->id)){
                if($request->id == "all"){
                    $filter = "";
                }else{
                    $filter = " and id_klien='$request->id' ";
                }
                $sql= "select id_klien, nama_klien from lab_daftar_klien
                where kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select id_klien, nama_klien from lab_daftar_klien
                where kode_lokasi='".$kode_lokasi."'";
            }

            $success['req'] = $request->all();

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

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'nama_klien' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

        try {
            if($request->hasfile('file_gambar')){
                
                $kode = $this->generateKode("lab_daftar_klien", "id_klien", $kode_lokasi."-CL".date('Ym').".", "0001");
                $nama = $request->nama_klien;
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                    Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
                
                DB::connection($this->db)->insert("insert into lab_daftar_klien(id_klien,nama_klien,kode_lokasi,file_gambar) values ('$kode','$nama','$kode_lokasi','$foto')");
                $success['status'] = true;
                $success['message'] = "Data Banner berhasil diupload.";
                $success['no_bukti'] = $kode;
                return response()->json($success, 200);
            }else{
                $success['status'] = false;
                $success['message'] = "File Gambar harus dilampirkan";
                return response()->json($success, 500);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 500);
        }
    }

    public function show(Request $request) {
        $this->validate($request, [
            'id_klien' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select id_klien, nama_klien, file_gambar from lab_daftar_klien where kode_lokasi = '$kode_lokasi' and id_klien = '$request->id_klien'");
            
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

    public function update(Request $request) {
        $this->validate($request, [
            'id_klien' => 'required',
            'nama_klien' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode = $request->id_klien;
            $nama = $request->nama_klien;
            if($request->hasfile('file_gambar')){ 
                $file = $request->file_gambar;
                $foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                                Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));

                DB::connection($this->db)
                    ->table('lab_daftar_klien')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_klien', $kode)
                    ->delete();

                DB::connection($this->db)->insert("insert into lab_daftar_klien (id_klien, nama_klien, kode_lokasi, file_gambar) values ('$kode', '$nama', '$kode_lokasi', '$foto')");
            } else {
                DB::connection($this->db)->update("update lab_daftar_klien set nama_klien = '$nama' where id_klien = '$kode' and kode_lokasi = '$kode_lokasi'");
            }

            $success['status'] = true;
            $success['message'] = "Data Klien berhasil diubah.";
            $success['no_bukti'] = $kode;
            return response()->json($success, $this->successStatus);            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 500);
        }
    }

}

?>