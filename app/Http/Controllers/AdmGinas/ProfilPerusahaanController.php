<?php

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfilPerusahaanController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'dbsaife';
    public $guard = 'admginas';

    public function getDataPerusahaanWA() {
        try {
            $kode_lokasi = '17';

            $res1 = DB::connection($this->db)->select("select link_wa 
                from lab_profil_perusahaan
                where kode_lokasi = '$kode_lokasi'");
            
            $res1 = json_decode(json_encode($res1),true);
            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
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

    public function getDataPerusahaanKontak() {
        try {
            $kode_lokasi = '17';

            $res1 = DB::connection($this->db)->select("select no_telp, alamat, no_fax, email 
                from lab_profil_perusahaan
                where kode_lokasi = '$kode_lokasi'");
            
            $res1 = json_decode(json_encode($res1),true);
            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
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

    public function getDataPerusahaanVMD() {
        try {
            $kode_lokasi = '17';

            $res1 = DB::connection($this->db)->select("select deskripsi, visi 
                from lab_profil_perusahaan
                where kode_lokasi = '$kode_lokasi'");

            $res2 = DB::connection($this->db)->select("select no_urut+1 as no_urut, misi from lab_profil_perusahaan a
                inner join lab_profil_perusahaan_detail b on a.kode_lokasi=b.kode_lokasi and a.id_perusahaan=b.id_perusahaan  
                where a.kode_lokasi = '$kode_lokasi'");
            
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
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'id_perusahaan' => 'required',
            'nama_perusahaan' => 'required',
            'wa' => 'required',
            'no_fax' => 'required',
            'koordinat' => 'required',
            'deskripsi' => 'required',
            'visi' => 'required',
            'misi' => 'required|array',
            'alamat' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        DB::connection($this->db)->beginTransaction();
        
        try {    
            $kode = $request->id_perusahaan;
            $nama = $request->nama_perusahaan;
            $koordinat = $request->koordinat;
            $deskripsi = $request->deskripsi;
            $visi = $request->visi;
            $alamat = $request->alamat;
            $telp = $request->no_telp;
            $email = $request->email;
            $wa = $request->wa;
            $fax = $request->no_fax;
            
            if($request->hasfile('file_gambar')){
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                    Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
            }else{
                $getFoto = DB::connection($this->db)->select("select top 1 file_gambar from lab_profil_perusahaan where kode_lokasi = '$kode_lokasi'");
                $res = json_decode(json_encode($getFoto),true);

                if($res[0]['file_gambar'] == null) {
                    $foto = '-';
                } else {
                    $foto = $res[0]['file_gambar'];
                }
            }
            
            // if(count($request->no_urut) > 0) {
            //     for($i=0;$i<count($request->no_urut);$i++) {
            //         $arr_no_urut[] = $request->no_urut[$i]; 
            //         $arr_misi[] = $request->misi[$i]; 
            //     }
            // }

            DB::connection($this->db)->table('lab_profil_perusahaan')->where('kode_lokasi', $kode_lokasi)->delete();
            DB::connection($this->db)->table('lab_profil_perusahaan_detail')->where('kode_lokasi', $kode_lokasi)->delete();

            DB::connection($this->db)->insert("insert into lab_profil_perusahaan(id_perusahaan,nama_perusahaan,koordinat,kode_lokasi,file_gambar,visi,alamat,deskripsi,no_telp,email,link_wa,no_fax) values ('$kode','$nama','$koordinat','$kode_lokasi','$foto','$visi','$alamat','$deskripsi','$telp','$email','$wa','$fax')");

            if(count($request->misi) > 0) {
                for($i=0;$i<count($request->misi);$i++) {
                    DB::connection($this->db)->insert("insert into lab_profil_perusahaan_detail(kode_lokasi,id_perusahaan,misi,no_urut) values ('$kode_lokasi','$kode','".$request->misi[$i]."',$i)");
                }
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Profil Perusahaan berhasil disimpan.";
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
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res1 = DB::connection($this->db)->select("select id_perusahaan, nama_perusahaan, koordinat, deskripsi, visi, alamat, no_telp, email, file_gambar, link_wa, no_fax
                from lab_profil_perusahaan
                where kode_lokasi = '$kode_lokasi'");

            $res2 = DB::connection($this->db)->select("select no_urut, misi from lab_profil_perusahaan a
                inner join lab_profil_perusahaan_detail b on a.kode_lokasi=b.kode_lokasi and a.id_perusahaan=b.id_perusahaan  
                where a.kode_lokasi = '$kode_lokasi'");
            
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
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }
}

?>