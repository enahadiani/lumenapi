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

    public function index(Request $request)
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT nama, nu, 
            CASE
                WHEN SUBSTRING(jenis, 1, 1) = 'S' THEN 'Suami'
                WHEN SUBSTRING(jenis, 1, 1) = 'I' THEN 'Istri'
                WHEN SUBSTRING(jenis, 1, 1) = 'A' THEN 'Anak'
                ELSE 'Tidak diketahui'
            END AS jenis,
            CASE 
                WHEN jk = 'L' THEN 'Laki-laki'
                WHEN jk = 'P' THEN 'Perempuan'
                ELSE 'Tidak diketahui'
            END AS jenis_kelamin
            from hr_keluarga
            WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."'";
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
            'nu' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT a.nik, a.nama, a.nu, a.jenis, a.jk, a.tempat, a.tgl_lahir, a.status_kes, a.foto,
            b.nama as nama_karyawan   
            FROM hr_keluarga a
            INNER JOIN hr_karyawan b ON a.nik=b.nik AND a.kode_lokasi=b.kode_lokasi
            WHERE a.nik = '".$request->nik."' AND a.kode_lokasi = '".$kode_lokasi."' AND a.nu = '".$request->nu."'";
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
            'nama' => 'required',
            'jenis' => 'required',
            'jk' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required',
            'status_kes' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $foto = NULL;
            if($request->hasFile('file')) {
                $file = $request->file('file');
                $nama_foto = "_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                    Storage::disk('s3')->delete('sdm/'.$nama_foto);
                }
                Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));
            }
                
            $insert = "INSERT INTO hr_keluarga(nik, kode_lokasi, jenis, nama, jk, tempat, tgl_lahir, 
            status_kes, foto) 
            VALUES ('".$nik."', '".$kode_lokasi."', '".$request->input('jenis')."', '".$request->input('nama')."',
            '".$request->input('jk')."', '".$request->input('tempat')."', '".$request->input('tgl_lahir')."', '".$request->input('status_kes')."', '".$foto."')";
            DB::connection($this->db)->insert($insert);
            $success['status'] = true;
            $success['message'] = "Data keluarga karyawan berhasil disimpan";
            
            $success['kode'] = $nik;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
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
            'nama' => 'required',
            'nu' => 'required',
            'jenis' => 'required',
            'jk' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required',
            'status_kes' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $select= "SELECT foto FROM hr_keluarga WHERE nik='".$nik."'
            AND kode_lokasi='".$kode_lokasi."'
            AND nu = '".$request->input('nu')."'";

            $result = DB::connection($this->db)->select($select);
            $foto = "-";
            
            if($request->hasFile('file')) {
                if(count($result) > 0){
                    if(Storage::disk('s3')->exists('sdm/'.$result[0]->foto)){
                        Storage::disk('s3')->delete('sdm/'.$result[0]->foto);
                    }
                }

                $file = $request->file('file');
                $nama_foto = "_".$file->getClientOriginalName();
                $foto = $nama_foto;
                Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));
            } else {
                $foto = $result[0]->foto;
            }

            $update = "UPDATE hr_keluarga SET jenis = '".$request->input('jenis')."', nama = '".$request->input('nama')."',
            jk = '".$request->input('jk')."', tempat = '".$request->input('tempat')."', tgl_lahir = '".$request->input('tgl_lahir')."', 
            status_kes = '".$request->input('status_kes')."', foto = '".$foto."'
            WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."' AND nu = '".$request->input('nu')."'";
            
            DB::connection($this->db)->update($update);

            $success['status'] = true;
            $success['message'] = "Data keluarga karyawan berhasil diubah";
            $success['kode'] = $nik;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
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
            'nu' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $select = "SELECT foto FROM hr_keluarga WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."'
            AND nu = '".$request->nu."'";
            $foto = DB::connection($this->db)->select($select);

            if(count($foto) > 0){ 
                if(Storage::disk('s3')->exists('sdm/'.$foto[0]->foto)){
                    Storage::disk('s3')->delete('sdm/'.$foto[0]->foto);
                }
            }
            
            DB::connection($this->db)->table('hr_keluarga')
            ->where('nik', $nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nu', $nu)
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
