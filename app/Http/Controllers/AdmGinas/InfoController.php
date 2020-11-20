<?php

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InfoController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'dbsaife';
    public $guard = 'admginas';

    public function getTop3Info() {
        try {
            $kode_lokasi = '17';

            $res1 = DB::connection($this->db)->select("select tanggal, judul, file_gambar 
                from lab_informasi
                where kode_lokasi = '$kode_lokasi'");
            
            $res1 = json_decode(json_encode($res1),true);
            if(count($res1) > 0 || count($res2) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($res1);$i++) {
                    $getDate = date('d',strtotime($res1[$i]['tanggal']));
                    $getMonth = date('m',strtotime($res1[$i]['tanggal']));
                    $getYear = date('Y',strtotime($res1[$i]['tanggal']));
                    $convert = floatval($getMonth);
                    $res1[$i]['tanggal'] = "$getDate $this->getNamaBulan($convert) $getYear";
                }

                $success['status'] = true;
                $success['data'] = $res1;
                $success['detail'] = $res2;
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
                    $filter = " and id_info='$request->id' ";
                }
                $sql= "select id_info, judul, tanggal from lab_informasi
                where kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select id_info, judul, tanggal from lab_informasi
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

    function getNamaBulan($bulan) {
        $arrayBulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 
        'September', 'Oktober', 'November', 'Desember');
        return $arrayBulan[$bulan-1];
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
            'tanggal' => 'required',
            'judul' => 'required',
            'content' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        DB::connection($this->db)->beginTransaction();
        
        try {    
            $kode = $this->generateKode("lab_informasi", "id_info", $kode_lokasi."-IF".date('Ym').".", "0001");
            $judul = $request->judul;
            $content = $request->content;
            $tanggal = $request->tanggal;
            
            if($request->hasfile('file_gambar')){
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                    Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
            }else{
                $success['status'] = false;
                $success['message'] = "File Gambar harus dilampirkan";
                return response()->json($success, 500);
            }

            DB::connection($this->db)->insert("insert into lab_informasi(id_info,tanggal,judul,content,file_gambar,kode_lokasi) values ('$kode','$tanggal','$judul','$content','$foto','$kode_lokasi')");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Informasi berhasil disimpan.";
            $success['no_bukti'] = $kode;
            return response()->json($success, 200);

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 500);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id_info' => 'required',
            'tanggal' => 'required',
            'judul' => 'required',
            'content' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        DB::connection($this->db)->beginTransaction();
        
        try {    
            $kode = $request->id_info;
            $judul = $request->judul;
            $content = $request->content;
            $tanggal = $request->tanggal;
            
            if($request->hasfile('file_gambar')){
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                    Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));

                DB::connection($this->db)
                    ->table('lab_informasi')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_info', $kode)
                    ->delete();
                
                DB::connection($this->db)->insert("insert into lab_informasi(id_info,tanggal,judul,content,file_gambar,kode_lokasi) values ('$kode','$tanggal','$judul','$content','$foto','$kode_lokasi')");
                
            }else{
                DB::connection($this->db)->update("update lab_informasi set tanggal = '$tanggal', judul = '$judul', content = '$content'
                where id_info = '$kode' and kode_lokasi = '$kode_lokasi'");
            }
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Informasi berhasil diubah.";
            $success['no_bukti'] = $kode;
            return response()->json($success, 200);

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 500);
        }
    }

    public function show(Request $request) {
        $this->validate($request, [
            'id_info' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res1 = DB::connection($this->db)->select("select id_info, tanggal, judul, content, file_gambar
                from lab_informasi
                where kode_lokasi = '$kode_lokasi'");
            
            $res1 = json_decode(json_encode($res1),true);
            if(count($res1) > 0 || count($res2) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
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
}

?>