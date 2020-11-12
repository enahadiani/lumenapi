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
                    $filter = " and a.id='$request->id' ";
                }
                $sql= "select a.id from lab_gbr_banner a
                inner join lab_gbr_banner_detail b on a.id=b.id and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select a.id from lab_gbr_banner a
                inner join lab_gbr_banner_detail b on a.id=b.id and a.kode_lokasi=b.kode_lokasi
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

    public function store(Request $request) {
        $this->validate($request, [
            'gambarke' => 'required|array'
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
                        $arr_gambarke[] = $request->gambarke[$i];
                    }
                    
                    $del = DB::connection($this->db)->table('lab_gbr_banner_detail')->where('kode_lokasi', $kode_lokasi)->delete();
                }

                if(count($arr_gambarke) > 0){
                    for($i=0; $i<count($arr_gambarke);$i++){
                        $ins[$i] = DB::connection($this->db)->insert("insert into lab_gbr_banner_detail (id,kode_lokasi,file_gambar,gambarke) values ('".$i."','$kode_lokasi','".$arr_foto[$i]."','".$arr_gambarke[$i]."') "); 
                    }
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['message'] = "Data Banner berhasil diupload.";
                    $success['no_bukti'] = "Tes";
                }
                else{
                    $success['status'] = false;
                    $success['message'] = "Data Banner gagal diupload. Banner file tidak valid. (2)";
                    $success['no_bukti'] = "Tes";
                }
            }else{
                $success['status'] = false;
                $success['message'] = "Data Banner gagal diupload. Banner file tidak valid. (3)";
                $success['no_bukti'] = "Tes";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Banner gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }
}

?>