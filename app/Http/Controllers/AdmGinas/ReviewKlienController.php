<?php 

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReviewKlienController extends Controller {
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
                    $filter = " and id_review='$request->id' ";
                }
                $sql= "select id_review, nama_client, nama_perusahaan, jabatan from lab_review_klien
                where kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select id_review, nama_client, nama_perusahaan, jabatan from lab_review_klien
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
            'nama_perusahaan' => 'required',
            'nama_klien' => 'required',
            'jabatan' => 'required',
            'deskripsi' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

        try {
            if($request->hasfile('file_gambar')){
                
                $kode = $this->generateKode("lab_review_klien", "id_review", $kode_lokasi."-RV".date('Ym').".", "0001");
                $nama = $request->nama_perusahaan;
                $jabatan = $request->jabatan;
                $deskripsi = $request->deskripsi;
                $client = $request->nama_klien;
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                    Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
                
                DB::connection($this->db)->insert("insert into lab_review_klien(id_review,nama_perusahaan,kode_lokasi,file_gambar,jabatan,deskripsi,nama_client) values ('$kode','$nama','$kode_lokasi','$foto','$jabatan','$deskripsi','$client')");
                $success['status'] = true;
                $success['message'] = "Data Review berhasil disimpan.";
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
            'id_review' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select id_review, nama_perusahaan, jabatan, deskripsi, file_gambar, nama_client from lab_review_klien where kode_lokasi = '$kode_lokasi' and id_review = '$request->id_review'");
            
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
            'nama_perusahaan' => 'required',
            'nama_klien' => 'required',
            'jabatan' => 'required',
            'deskripsi' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode = $request->id_review;
            $nama = $request->nama_perusahaan;
            $jabatan = $request->jabatan;
            $deskripsi = $request->deskripsi;
            $client = $request->nama_klien;
            if($request->hasfile('file_gambar')){ 
                $file = $request->file_gambar;
                $foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                                Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));

                DB::connection($this->db)
                    ->table('lab_review_klien')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_review', $kode)
                    ->delete();

                DB::connection($this->db)->insert("insert into lab_review_klien(id_review,nama_perusahaan,kode_lokasi,file_gambar,jabatan,deskripsi, nama_client) values ('$kode','$nama','$kode_lokasi','$foto','$jabatan','$deskripsi','$client')");
            } else {
                DB::connection($this->db)->update("update lab_review_klien set nama_client = '$client',nama_perusahaan = '$nama', jabatan = '$jabatan', deskripsi = '$deskripsi' where id_review = '$kode' and kode_lokasi = '$kode_lokasi'");
            }

            $success['status'] = true;
            $success['message'] = "Data Review berhasil diubah.";
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