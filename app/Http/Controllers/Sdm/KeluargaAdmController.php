<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KeluargaAdmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getNU($nik, $kode_lokasi) {
        $select= "SELECT max(nu) AS nu FROM hr_keluarga WHERE nik='".$nik."' AND kode_lokasi='".$kode_lokasi."'";
        $result = DB::connection($this->db)->select($select);
        $nu = NULL;

        if(count($result) > 0){
            $nu = $result[0]->nu + 1;
        } else {
            $nu = 1;
        }

        return $nu;
    }

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

            $sql = "SELECT a.nik, a.nama, isnull(b.jum,0) AS jum
            FROM hr_karyawan a
            LEFT JOIN (select a.nik,a.kode_lokasi,count(*) AS jum
			FROM hr_keluarga a
		    GROUP BY a.nik,a.kode_lokasi
			) b ON a.nik=b.nik AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."'";
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

            $karyawan = "SELECT nik, nama FROM hr_karyawan WHERE nik = '".$request->query('nik')."' 
            AND kode_lokasi = '".$kode_lokasi."'";
            $resKaryawan = DB::connection($this->db)->select($karyawan);
            $resKaryawan = json_decode(json_encode($resKaryawan),true);

            $sql = "SELECT nama, jenis, jk, tempat, tgl_lahir, status_kes, foto   
            FROM hr_keluarga
            WHERE nik = '".$request->query('nik')."' AND kode_lokasi = '".$kode_lokasi."'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $resKaryawan;
                $success['detail'] = $res;
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
                if(!empty($request->file('file'))) {
                    if(count($request->file('file')) > 0) {
                        for($j=0;$j<count($request->file('file'));$j++) {
                            $file = $request->file('file')[$j];
                            $nama_foto = "_".$file->getClientOriginalName();
                            
                            if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                                Storage::disk('s3')->delete('sdm/'.$nama_foto);
                            }
                            Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));     
                        }
                    }
                }

                for($i=0;$i<count($request->input('nomor'));$i++) { 
                    $nu = $this->getNU($request->input('nik'),$kode_lokasi);
                    $fileName = $request->input('fileName');   
                    $jenis = $request->input('jenis');
                    $nama = $request->input('nama');
                    $jk = $request->input('jk');
                    $tempat = $request->input('tempat');
                    $tgl_lahir = $request->input('tgl_lahir');
                    $tanggungan = $request->input('status_kes');

                    $insert = "INSERT INTO hr_keluarga(nik, kode_lokasi, jenis, nama, jk, tempat, tgl_lahir, 
                    status_kes, foto) 
                    VALUES ('".$request->input('nik')."', '".$kode_lokasi."', '".$jenis[$i]."', '".$nama[$i]."',
                    '".$jk[$i]."', '".$tempat[$i]."', '".$tgl_lahir[$i]."', '".$tanggungan[$i]."', '".$fileName[$i]."')";
                    DB::connection($this->db)->insert($insert);
                }

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data keluarga karyawan berhasil disimpan";
                $success['kode'] = $request->input('nik');
            } else {
                $success['kode'] = $request->input('nik');
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK keluarga karyawan sudah ada di database!";
            }

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
            
            if(!empty($request->file('file'))) { 
                if(count($request->file('file')) > 0) {
                    for($j=0;$j<count($request->file('file'));$j++) {
                        $file = $request->file('file')[$j];
                        $nama_foto = "_".$file->getClientOriginalName();
                            
                        if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                            Storage::disk('s3')->delete('sdm/'.$nama_foto);
                        }
                        Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));     
                    }
                }
            }

            DB::connection($this->db)->table('hr_keluarga')
            ->where('nik', $request->nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();

            for($i=0;$i<count($request->input('nomor'));$i++) { 
                $nu = $this->getNU($request->input('nik'),$kode_lokasi);
                $fileName = $request->input('fileName'); 
                $filePrevName = $request->input('filePrevName');
                $isUpload = $request->input('isUpload');  
                $jenis = $request->input('jenis');
                $nama = $request->input('nama');
                $jk = $request->input('jk');
                $tempat = $request->input('tempat');
                $tgl_lahir = $request->input('tgl_lahir');
                $tanggungan = $request->input('status_kes');

                if($isUpload[$i] === 'false') { // kalo gak upload
                    $insert = "INSERT INTO hr_keluarga(nik, kode_lokasi, jenis, nama, jk, tempat, tgl_lahir, 
                    status_kes, foto) 
                    VALUES ('".$request->input('nik')."', '".$kode_lokasi."', '".$jenis[$i]."', '".$nama[$i]."',
                    '".$jk[$i]."', '".$tempat[$i]."', '".$tgl_lahir[$i]."', '".$tanggungan[$i]."', '".$filePrevName[$i]."')";
                } else {
                    $insert = "INSERT INTO hr_keluarga(nik, kode_lokasi, jenis, nama, jk, tempat, tgl_lahir, 
                    status_kes, foto) 
                    VALUES ('".$request->input('nik')."', '".$kode_lokasi."', '".$jenis[$i]."', '".$nama[$i]."',
                    '".$jk[$i]."', '".$tempat[$i]."', '".$tgl_lahir[$i]."', '".$tanggungan[$i]."', '".$fileName[$i]."')";
                }
                
                DB::connection($this->db)->insert($insert);
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data keluarga karyawan berhasil diubah";
            $success['kode'] = $request->input('nik');
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

            $select = "SELECT foto FROM hr_keluarga WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."'";
            $foto = DB::connection($this->db)->select($select);

            if(count($foto) > 0){ 
                for($i=0;$i<count($foto);$i++) { 
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
