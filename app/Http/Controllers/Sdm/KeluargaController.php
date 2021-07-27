<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KeluargaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function isUnik($isi, $kode_lokasi){
        
        $auth = DB::connection($this->db)->select("SELECT nik FROM hr_keluarga WHERE nik ='".$isi."' AND kode_lokasi = '".$kode_lokasi."'");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT nik, nama FROM hr_keluarga WHERE kode_lokasi = '".$kode_lokasi."' ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function show(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT nik, nama, nu, jenis, jk, tempat, tgl_lahir, status_kes, foto   
            FROM hr_keluarga
            WHERE nik = '".$request->nik."' AND kode_lokasi = '".$kode_lokasi."'";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";

                return response()->json($success, $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required',
            'nama' => 'required|array',
            'nomor' => 'required|array',
            'jenis' => 'required|array',
            'jk' => 'required|array',
            'tempat' => 'required|array',
            'tgl_lahir' => 'required|array',
            'status_kes' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($request->input('nik'), $kode_lokasi)) {
                if(count($request->file('file')) > 0) {
                    for($j=0;$j<count($request->file('file'));$j++) {
                        $file = $request->file('file');
                        $nama_foto = "_".$file->getClientOriginalName();
                        
                        if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                            Storage::disk('s3')->delete('sdm/'.$nama_foto);
                        }
                        Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));     
                    }
                }
                
                for($i=0;$i<count($request->input('nomor'));$i++) {
                    $nik = $request->input('nik');
                    $nama = $request->input('nama');
                    $nomor = $request->input('nomor');
                    $jenis = $request->input('jenis');
                    $jk = $request->input('jk');
                    $tempat = $request->input('tempat');
                    $tgl_lahir = $request->input('tgl_lahir');
                    $statusKes = $request->input('status_kes');
                    $fileName = $request->input('fileName');
                    

                    $insert = "INSERT INTO hr_keluarga(nik, kode_lokasi, nu, jenis, nama, jk, tempat, tgl_lahir, 
                    status_kes, foto) 
                    VALUES ('".$nik."', '".$kode_lokasi."', '".$nomor[$i]."', '".$jenis[$i]."', '".$nama[$i]."',
                    '".$jk[$i]."', '".$tempat[$i]."', '".$tgl_lahir[$i]."', '".$statusKes[$i]."', '".$fileName[$i]."')";

                    DB::connection($this->db)->insert($insert);   
                }
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data keluarga karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK keluarga karyawan sudah ada di database!";
            }
            $success['kode'] = $request->nik;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data keluarga karyawan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required',
            'nama' => 'required|array',
            'nomor' => 'required|array',
            'jenis' => 'required|array',
            'jk' => 'required|array',
            'tempat' => 'required|array',
            'tgl_lahir' => 'required|array',
            'status_kes' => 'required|array'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(count($request->file('file')) > 0) {
                for($j=0;$j<count($request->file('file'));$j++) {
                    $file = $request->file('file');
                    $nama_foto = "_".$file->getClientOriginalName();
                        
                    if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                        Storage::disk('s3')->delete('sdm/'.$nama_foto);
                    }
                    Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));     
                }
            }

            DB::connection($this->db)->table('hr_keluarga')
            ->where('nik', $request->nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();


            for($i=0;$i<count($request->input('nomor'));$i++) {
                $nik = $request->input('nik');
                $nama = $request->input('nama');
                $nomor = $request->input('nomor');
                $jenis = $request->input('jenis');
                $jk = $request->input('jk');
                $tempat = $request->input('tempat');
                $tgl_lahir = $request->input('tgl_lahir');
                $statusKes = $request->input('status_kes');
                $fileName = $request->input('fileName');
                $filePrevName = $request->input('filePrevName');
                $isUpload = $request->input('isUpload');
                
                if($isUpload[$i] === 'false') {
                    $insert = "INSERT INTO hr_keluarga(nik, kode_lokasi, nu, jenis, nama, jk, tempat, tgl_lahir, 
                    status_kes, foto) 
                    VALUES ('".$nik."', '".$kode_lokasi."', '".$nomor[$i]."', '".$jenis[$i]."', '".$nama[$i]."',
                    '".$jk[$i]."', '".$tempat[$i]."', '".$tgl_lahir[$i]."', '".$statusKes[$i]."', '".$filePrevName[$i]."')";
                } else {
                    Storage::disk('s3')->delete('sdm/'.$filePrevName[$i]);
                    $insert = "INSERT INTO hr_keluarga(nik, kode_lokasi, nu, jenis, nama, jk, tempat, tgl_lahir, 
                    status_kes, foto) 
                    VALUES ('".$nik."', '".$kode_lokasi."', '".$nomor[$i]."', '".$jenis[$i]."', '".$nama[$i]."',
                    '".$jk[$i]."', '".$tempat[$i]."', '".$tgl_lahir[$i]."', '".$statusKes[$i]."', '".$fileName[$i]."')";
                }

                DB::connection($this->db)->insert($insert);   
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data keluarga karyawan berhasil diubah";
            $success['kode'] = $request->nik;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data keluarga karyawan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $select = "SELECT foto FROM hr_keluarga WHERE nik = '".$request->nik."' AND kode_lokasi = '".$kode_lokasi."'";
            $foto = DB::connection($this->db)->select($select);

            if(count($foto) > 0){ 
                for($i;$i<count($foto);$i++) {
                    if(Storage::disk('s3')->exists('sdm/'.$foto[$i]->foto)){
                        Storage::disk('s3')->delete('sdm/'.$foto[$i]->foto);
                    }
                }
            }
            
            DB::connection($this->db)->table('hr_keluarga')
            ->where('nik', $request->nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            $success['status'] = true;
            $success['message'] = "Data keluarga karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data keluarga karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
