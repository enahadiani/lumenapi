<?php

namespace App\Http\Controllers\Sekolah;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SiswaInputController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "siswa";
    public $db = "sqlsrvtarbak";

    public function isUnik($isi,$kode_lokasi){   
        $auth = DB::connection($this->db)->select("select nis from sis_siswa where nis ='".$isi."' and kode_lokasi='".$kode_lokasi."'");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function save(Request $r) {
        $this->validate($r,[
            'nis' => 'required',
            'flag_aktif' => 'required',
            'kode_kelas' => 'required',
            'kode_akt' => 'required',
            'nama' => 'required',
            'tmp_lahir' => 'required',
            'tgl_lahir' => 'required',
            'jk' => 'required',
            'agama' => 'required',
            'hp_siswa' => 'required',
            'email' => 'required',
            'alamat_siswa' => 'required',
            'nama_wali' => 'required',
            'alamat_wali' => 'required',
            'kerja_wali' => 'required',
            'hp_wali' => 'required',
            'email_wali' => 'required',
            'gol_darah' => 'required',
            'id_bank' => 'required',
            'nis2' => 'required',
            'kode_pp' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($r->input('nis'), $kode_lokasi)) {
                $insert = "INSERT INTO sis_siswa (nis, flag_aktif, kode_kelas, kode_akt, nama, tmp_lahir, tgl_lahir, 
                jk, agama, hp_siswa, email, alamat_siswa, nama_wali, alamat_wali, kerja_wali, hp_wali, email_wali, 
                gol_darah, id_bank, nis2, kode_pp, kode_lokasi) VALUES (? , ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?)";

                DB::connection($this->db)->insert($insert, [
                    $r->input('nis'),
                    $r->input('flag_aktif'),
                    $r->input('kode_kelas'),
                    $r->input('kode_akt'),
                    $r->input('nama'),
                    $r->input('tmp_lahir'),
                    $r->input('tgl_lahir'),
                    $r->input('jk'),
                    $r->input('agama'),
                    $r->input('hp_siswa'),
                    $r->input('email'),
                    $r->input('alamat_siswa'),
                    $r->input('nama_wali'),
                    $r->input('alamat_wali'),
                    $r->input('kerja_wali'),
                    $r->input('hp_wali'),
                    $r->input('email_wali'),
                    $r->input('gol_darah'),
                    $r->input('id_bank'),
                    $r->input('nis2'),
                    $r->input('kode_pp'),
                    $kode_lokasi
                ]);

                $success['status'] = true;
                $success['kode'] = $r->input('nis');
                $success['message'] = "Data siswa berhasil disimpan";
            } else {
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIS siswa sudah ada di database!";
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    
}
