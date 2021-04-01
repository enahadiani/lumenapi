<?php
namespace App\Http\Controllers\JavaAdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller { 
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
    */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function index(Request $request) {
        try { 
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $selectPerusahaan = "select id_perusahaan, nama_perusahaan, koordinat, deskripsi, visi, alamat, no_telp, 
            email, path_foto, link_wa, no_fax
            from javaadmin_profil_perusahaan
            where kode_lokasi = '$kode_lokasi'";

            $selectDetail = "select no_urut, misi from javaadmin_profil_perusahaan a
            inner join javaadmin_profil_perusahaan_detail b on a.kode_lokasi=b.kode_lokasi 
            and a.id_perusahaan=b.id_perusahaan  
            where a.kode_lokasi = '$kode_lokasi'";

            $res1 = DB::connection($this->sql)->select($selectPerusahaan);
            $res2 = DB::connection($this->sql)->select($selectDetail);

            $res1 = json_decode(json_encode($res1),true);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res1) > 0 || count($res2) > 0){ //mengecek apakah data kosong atau tidak
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

        } catch(\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function store(Request $request) {
        $this->validate($request, [
            'id_perusahaan' => 'required',
            'nama_perusahaan' => 'required',
            'wa' => 'required',
            'no_fax' => 'required',
            'koordinat' => 'required',
            'deskripsi' => 'required',
            'visi' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'file' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        DB::connection($this->sql)->beginTransaction();
        try { 
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file')){
                $file = $request->file('file');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webjava/'.$foto)){
                    Storage::disk('s3')->delete('webjava/'.$foto);
                }
                Storage::disk('s3')->put('webjava/'.$foto,file_get_contents($file));
            }else{
                $getFoto = DB::connection($this->sql)->select("select top 1 path_foto from javaadmin_profil_perusahaan where kode_lokasi = '$kode_lokasi'");
                $res = json_decode(json_encode($getFoto),true);

                if($res[0]['path_foto'] == null) {
                    $foto = '-';
                } else {
                    $foto = $res[0]['path_foto'];
                }
            }

            $arr_no_urut = array();
            $arr_misi = array();

            if(count($request->no_urut) > 0) {
                for($i=0;$i<count($request->no_urut);$i++) {
                    $arr_no_urut[] = $request->no_urut[$i]; 
                    $arr_misi[] = $request->misi[$i]; 
                }
            }

            DB::connection($this->sql)->table('javaadmin_profil_perusahaan')->where('kode_lokasi', $kode_lokasi)->delete();
            DB::connection($this->sql)->table('javaadmin_profil_perusahaan_detail')->where('kode_lokasi', $kode_lokasi)->delete();

            $insert = "insert into javaadmin_profil_perusahaan 
            (id_perusahaan, nama_perusahaan, koordinat, kode_lokasi, path_foto, visi, alamat, deskripsi, no_telp, email, 
            link_wa, no_fax) values 
            ('".$request->kode."','".$request->nama."','".$request->koordinat."','".$kode_lokasi."','".$foto."',
            '".$request->visi."','".$request->alamat."','".$request->deskripsi."','".$request->telp."', 
            '".$request->email."', '".$request->wa."', '".$request->fax."')";

            DB::connection($this->sql)->insert($insert);

            if(count($arr_no_urut) > 0) {
                for($i=0;$i<count($arr_no_urut);$i++) {
                    $detail = "insert into javaadmin_profil_perusahaan_detail (kode_lokasi, id_perusahaan, misi, no_urut) 
                    values ('".$kode_lokasi."', '".$request->kode."', '".$arr_misi[$i]."', '".$arr_no_urut[$i]."')";
                    DB::connection($this->sql)->insert($detail);
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode;
            $success['message'] = "Data Perusahaan berhasil disimpan";
            
            return response()->json($success, $this->successStatus);   
        } catch(\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Perusahaan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}

?>