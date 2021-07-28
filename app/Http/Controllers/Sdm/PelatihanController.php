<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PelatihanController extends Controller
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
        $select= "SELECT max(nu) AS nu FROM hr_pelatihan WHERE nik='".$nik."' AND kode_lokasi='".$kode_lokasi."'";
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

            $sql = "SELECT nama, panitia, convert(varchar,tgl_mulai,103) as tgl_mulai,
            convert(varchar,tgl_selesai,103) as tgl_selesai 
            FROM hr_pelatihan 
            WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."' ";
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

            $sql = "SELECT a.nik, a.nama, a.nu, a.panitia, a.sertifikat, convert(varchar,a.tgl_mulai,103) as tgl_mulai,
            convert(varchar,a.tgl_selesai,103) as tgl_selesai, b.nama as nama_karyawan   
            FROM hr_pelatihan a
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
            'panitia' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $foto = "-";
            if($request->hasFile('file')) {
                $file = $request->file('file');
                $nama_foto = "_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('sdm/'.$nama_foto)){
                    Storage::disk('s3')->delete('sdm/'.$nama_foto);
                }
                Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));
            }

            $insert = "INSERT INTO hr_pelatihan(nik, kode_lokasi, nama, panitia, sertifikat, tgl_mulai,
            tgl_selesai) 
            VALUES ('".$nik."', '".$kode_lokasi."', '".$request->input('nama')."',
            '".$request->input('panitia')."', '".$foto."', '".$request->input('tgl_mulai')."',
            '".$request->input('tgl_selesai')."')";

            DB::connection($this->db)->insert($insert);

            $success['kode'] = $nik;
            $success['status'] = true;
            $success['message'] = "Data pelatihan karyawan berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data pelatihan karyawan gagal disimpan ".$e;
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
            'panitia' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required',
            'nu' => 'required'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $select= "SELECT sertifikat FROM hr_pelatihan WHERE nik='".$nik."'
            AND kode_lokasi='".$kode_lokasi."'
            AND nu = '".$request->input('nu')."'";

            $result = DB::connection($this->db)->select($select);
            $foto = NULL;
            
            if($request->hasFile('file')) {
                if(count($result) > 0){
                    if(Storage::disk('s3')->exists('sdm/'.$result[0]->sertifikat)){
                        Storage::disk('s3')->delete('sdm/'.$result[0]->sertifikat);
                    }
                }

                $file = $request->file('file');
                $nama_foto = "_".$file->getClientOriginalName();
                $foto = $nama_foto;
                Storage::disk('s3')->put('sdm/'.$nama_foto,file_get_contents($file));
            } else {
                $foto = $result[0]->foto;
            }

            $update = "UPDATE hr_pelatihan SET nama = '".$request->input('nama')."', panitia = '".$request->input('panitia')."',
            sertifikat = '".$foto."', tgl_mulai = '".$request->input('tgl_mulai')."',
            tgl_selesai = '".$request->input('tgl_selesai')."' WHERE nik = '".$nik."'
            AND kode_lokasi = '".$kode_lokasi."' AND nu = '".$request->input('nu')."'";
            
            DB::connection($this->db)->update($update);

            $success['status'] = true;
            $success['message'] = "Data pelatihan karyawan berhasil diubah";
            $success['kode'] = $nik;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data pelatihan karyawan gagal diubah ".$e;
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

            $select = "SELECT sertifikat FROM hr_pelatihan WHERE nik = '".$nik."' AND kode_lokasi = '".$kode_lokasi."'
            AND nu = '".$request->nu."'";
            $foto = DB::connection($this->db)->select($select);

            if(count($foto) > 0){ 
                if(Storage::disk('s3')->exists('sdm/'.$foto[0]->sertifikat)){
                    Storage::disk('s3')->delete('sdm/'.$foto[0]->sertifikat);
                }
            }
            
            DB::connection($this->db)->table('hr_pelatihan')
            ->where('nik', $nik)
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nu', $request->nu)
            ->delete();

            $success['status'] = true;
            $success['message'] = "Data pelatihan karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data pelatihan karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
