<?php 

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SertifikatController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'dbsaife';
    public $guard = 'admginas';

    public function showReview() {
        try {
            $kode_lokasi= '17';

            $sql= "select top 3 nama_perusahaan, jabatan, deskripsi, file_gambar, nama_client from lab_review_klien
                where kode_lokasi='".$kode_lokasi."' order by id_review desc";
                
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
                    $filter = " and id_review='$request->id' ";
                }
                $sql= "select id_sertifikat, nama_sertifikat from lab_sertifikat
                where kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select id_sertifikat, nama_sertifikat from lab_sertifikat
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
            'nama_sertifikat' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

        try {
            if($request->hasfile('file_gambar')){
                
                $kode = $this->generateKode("lab_sertifikat", "id_sertifikat", $kode_lokasi."-ST".date('Ym').".", "0001");
                $nama = $request->nama_sertifikat;
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                    Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
                
                DB::connection($this->db)->insert("insert into lab_sertifikat(id_sertifikat,nama_sertifikat,kode_lokasi,file_gambar) values ('$kode','$nama','$kode_lokasi','$foto')");
                $success['status'] = true;
                $success['message'] = "Data Sertifikat berhasil disimpan.";
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
            'id_sertifikat' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select id_sertifikat, nama_sertifikat, file_gambar from lab_sertifikat where kode_lokasi = '$kode_lokasi' and id_sertifikat = '$request->id_sertifikat'");
            
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
            'nama_sertifikat' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode = $request->id_sertifikat;
            $nama = $request->nama_sertifikat;
            if($request->hasfile('file_gambar')){ 
                $file = $request->file_gambar;
                $foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                                Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));

                DB::connection($this->db)
                    ->table('lab_sertifikat')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_sertifikat', $kode)
                    ->delete();

                DB::connection($this->db)->insert("insert into lab_sertifikat(id_sertifikat,nama_sertifikat,kode_lokasi,file_gambar) values ('$kode','$nama','$kode_lokasi','$foto')");
            } else {
                DB::connection($this->db)->update("update lab_sertifikat set nama_sertifikat = '$nama' where id_sertifikat = '$kode' and kode_lokasi = '$kode_lokasi'");
            }

            $success['status'] = true;
            $success['message'] = "Data Sertifikat berhasil diubah.";
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