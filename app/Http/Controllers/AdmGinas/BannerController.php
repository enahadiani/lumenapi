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

    public function show(Request $request) {

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_lokasi= '17';

            $res1 = DB::connection($this->db)->select("select file_gambar, mode from lab_gbr_banner where kode_lokasi = '$kode_lokasi' and mode = 'WEB'");
            $res2 = DB::connection($this->db)->select("select file_gambar, mode from lab_gbr_banner where kode_lokasi = '$kode_lokasi' and mode = 'MOBILE'");
            
            $res1 = json_decode(json_encode($res1),true);
            $res2 = json_decode(json_encode($res2),true);
            if(count($res1) > 0 || count($res2) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_mobile'] = $res2;
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
            'gambarke' => 'required|array',
            'id_banner' => 'required|array',
            'mode' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $arr_bann = array();
            $arr_insert = array();
            $i=0;
            $cek = $request->file_gambar;
            if(!empty($cek)){
                if(count($request->gambarke) > 0){
                    for($i=0;$i<count($request->gambarke);$i++){
                        if(isset($request->file('file_gambar')[$i])){
                            $file = $request->file('file_gambar')[$i];
                            $id = $request->id_banner[$i];
                            $mode = $request->mode[$i];
                            $foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                            $data = DB::connection($this->db)->select("select id_banner, file_gambar from lab_gbr_banner where kode_lokasi = '$kode_lokasi' and id_banner = '$id'");
                            if(count($data) > 0) {
                                Storage::disk('s3')->delete('webginas/'.$data[0]['file_gambar']);
                                DB::connection($this->db)->update("update lab_gbr_banner set file_gambar = '$foto' where id_banner = '$data[0]['id_banner']'");
                                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
                            } else {
                                $arr_insert[] = "insert into lab_gbr_banner (id_banner,kode_lokasi,file_gambar,mode) values ('$id','$kode_lokasi','$foto','$mode') ";
                                DB::connection($this->db)->insert("insert into lab_gbr_banner (id_banner,kode_lokasi,file_gambar,mode) values ('$id','$kode_lokasi','$foto','$mode') ");
                                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
                            }
                        }
                        $arr_gambarke[] = $request->gambarke[$i];
                        $arr_mode[] = $request->mode[$i];
                    }
                }

                if(count($arr_gambarke) > 0){
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['data'] = $arr_gambarke;
                    $success['data2'] = $arr_mode;
                    $success['data3'] = $arr_insert;
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
}

?>