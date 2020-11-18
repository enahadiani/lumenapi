<?php 

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class BannerController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'dbsaife';
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
                    $filter = " and a.id_banner='$request->id' ";
                }
                $sql= "select distinct a.id_banner from lab_gbr_banner a
                inner join lab_gbr_banner_detail b on a.id_banner=b.id_banner and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select distinct a.id_banner from lab_gbr_banner a
                inner join lab_gbr_banner_detail b on a.id_banner=b.id_banner and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."'";
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

    public function store(Request $request) {
        $this->validate($request, [
            'gambarke' => 'required|array',
            'id_banner' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $arr_foto = array();
            $arr_gambarke = array();
            $i=0;
            $cek = $request->file_gambar;
            if(!empty($cek)){
                if(count($request->gambarke) > 0){
                    for($i=0;$i<count($request->gambarke);$i++){
                        if(isset($request->file('file_gambar')[$i])){

                            $file = $request->file('file_gambar')[$i];
                            $foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            if(Storage::disk('s3')->exists('webginas/'.$foto)){
                                Storage::disk('s3')->delete('webginas/'.$foto);
                            }
                            Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
                            $arr_foto[] = $foto;
                        }else{
                            $arr_foto[] = "-";
                        }
                        $arr_id_banner[] = $request->id_banner[$i];     
                        $arr_gambarke[] = $request->gambarke[$i];
                    }
                    
                    $del = DB::connection($this->db)->table('lab_gbr_banner')->where('kode_lokasi', $kode_lokasi)->delete();
                }

                if(count($arr_gambarke) > 0){
                    for($i=0; $i<count($arr_gambarke);$i++){
                        $ins[$i] = DB::connection($this->db)->insert("insert into lab_gbr_banner (id_banner,kode_lokasi,file_gambar) values ('$arr_id_banner[$i]','$kode_lokasi','$arr_foto[$i]') "); 
                    }
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['message'] = "Data Banner berhasil diupload.";
                    $success['no_bukti'] = "0";
                }
                else{
                    $success['status'] = false;
                    $success['message'] = "Data Banner gagal diupload. Banner file tidak valid. (2)";
                    $success['no_bukti'] = "0";
                }
            }else{
                $success['status'] = false;
                $success['message'] = "Data Banner gagal diupload. Banner file tidak valid. (3)";
                $success['no_bukti'] = "0";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Banner gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show(Request $request) {
        $this->validate($request, [
            'id_banner' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select file_gambar from lab_gbr_banner_detail where kode_lokasi = '$kode_lokasi' and id_banner = '$request->id_banner'");
            
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
}

?>