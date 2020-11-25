<?php
namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class SublayananController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'dbsaife';
    public $guard = 'admginas';

    public function isUnik($isi,$kode_lokasi){
        $auth = DB::connection($this->sql)->select("select id_sublayanan from lab_sublayanan where id_sublayanan ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function showKlien() {
        try {
            $kode_lokasi= '17';

            $sql= "select nama_klien, file_gambar from lab_daftar_klien
                where kode_lokasi='".$kode_lokasi."'";
                
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
                    $filter = " and b.id_sublayanan='$request->id' ";
                }
                $sql= "select b.id_sublayanan, b.nama_sublayanan, a.id_layanan from lab_layanan a
                inner join lab_detail_layanan c on a.kode_lokasi=c.kode_lokasi and a.id_layanan=c.id_layanan
                inner join lab_sublayanan b on c.kode_lokasi=b.kode_lokasi and c.id_sublayanan=b.id_sublayanan   
                where b.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select b.id_sublayanan, b.nama_sublayanan, a.id_layanan from lab_layanan a
                inner join lab_detail_layanan c on a.kode_lokasi=c.kode_lokasi and a.id_layanan=c.id_layanan
                inner join lab_sublayanan b on c.kode_lokasi=b.kode_lokasi and c.id_sublayanan=b.id_sublayanan
                where b.kode_lokasi='".$kode_lokasi."'";
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'id_sublayanan' => 'required',
            'nama_sublayanan' => 'required',
            'deskripsi_singkat' => 'required',
            'deskripsi' => 'required',
            'id_layanan' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

        try {
            if($request->hasfile('file_gambar')){
                if($this->isUnik($request->id_layanan,$kode_lokasi)){    
                    
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('webginas/'.$foto)){
                        Storage::disk('s3')->delete('webginas/'.$foto);
                    }
                    Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));

                    DB::connection($this->sql)->insert("insert into lab_sublayanan(id_sublayanan,nama_sublayanan,kode_lokasi,deskripsi_singkat,deskripsi,file_gambar) values ('".$request->id_sublayanan."','".$request->nama_sublayanan."','".$kode_lokasi."','".$request->deskrispi_singkat."','".$request->deskripsi."','$foto')");
                    DB::connection($this->sql)->insert("insert into lab_detail_layanan(id_sublayanan,id_layanan,kode_lokasi) values ('".$request->id_sublayanan."','".$request->id_layanan."','".$kode_lokasi."')");
                    DB::connection($this->sql)->commit();
                    
                    $success['status'] = true;
                    $success['message'] = "Data Sublayanan berhasil disimpan.";
                    $success['no_bukti'] = $request->id_sublayanan;

                }else{
                    $success['status'] = false;
                    $success['message'] = "Error : Duplicate entry. Kode Sublayanan sudah ada di database!";
                }

                return response()->json($success, 200);
        
            }else{
                DB::connection($this->sql)->rollback();
                $success['status'] = false;
                $success['message'] = "File Gambar harus dilampirkan";
                return response()->json($success, 500);
            }
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 500);
        }
    }

    public function show(Request $request) {
        $this->validate($request, [
            'id_sublayanan' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("
            select b.id_sublayanan, b.nama_sublayanan, a.id_layanan, a.nama_layanan, b.deskripsi_singkat, b.deskripsi, b.foto 
            from lab_layanan a
            inner join lab_detail_layanan c on a.kode_lokasi=c.kode_lokasi and a.id_layanan=c.id_layanan
            inner join lab_sublayanan b on c.kode_lokasi=b.kode_lokasi and c.id_sublayanan=b.id_sublayanan 
            where b.kode_lokasi = '$kode_lokasi' and b.id_sublayanan = '$request->id_sublayanan'");
            
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
            'id_sublayanan' => 'required',
            'nama_sublayanan' => 'required',
            'deskripsi_singkat' => 'required',
            'deskripsi' => 'required',
            'id_layanan' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){ 
                $file = $request->file_gambar;
                $foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                                Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));

                DB::connection($this->db)
                    ->table('lab_sublayanan')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_sublayanan', $request->id_sublayanan)
                    ->delete();
                
                DB::connection($this->db)
                    ->table('lab_detail_layanan')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_sublayanan', $request->id_sublayanan)
                    ->where('id_layanan', $request->id_layanan)
                    ->delete();

                DB::connection($this->sql)->insert("insert into lab_sublayanan(id_sublayanan,nama_sublayanan,kode_lokasi,deskripsi_singkat,deskripsi,file_gambar) values ('".$request->id_sublayanan."','".$request->nama_sublayanan."','".$kode_lokasi."','".$request->deskrispi_singkat."','".$request->deskripsi."','$foto')");
                DB::connection($this->sql)->insert("insert into lab_detail_layanan(id_sublayanan,id_layanan,kode_lokasi) values ('".$request->id_sublayanan."','".$request->id_layanan."','".$kode_lokasi."')");
            } else {
                DB::connection($this->db)
                    ->table('lab_sublayanan')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_sublayanan', $request->id_sublayanan)
                    ->delete();
                
                DB::connection($this->db)
                    ->table('lab_detail_layanan')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('id_sublayanan', $request->id_sublayanan)
                    ->where('id_layanan', $request->id_layanan)
                    ->delete();

                DB::connection($this->sql)->insert("insert into lab_sublayanan(id_sublayanan,nama_sublayanan,kode_lokasi,deskripsi_singkat,deskripsi,file_gambar) values ('".$request->id_sublayanan."','".$request->nama_sublayanan."','".$kode_lokasi."','".$request->deskrispi_singkat."','".$request->deskripsi."','$foto')");
                DB::connection($this->sql)->insert("insert into lab_detail_layanan(id_sublayanan,id_layanan,kode_lokasi) values ('".$request->id_sublayanan."','".$request->id_layanan."','".$kode_lokasi."')");
            }

            $success['status'] = true;
            $success['message'] = "Data Sublayanan berhasil diubah.";
            $success['no_bukti'] = $request->id_sublayanan;
            return response()->json($success, $this->successStatus);            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 500);
        }
    }

}

?>