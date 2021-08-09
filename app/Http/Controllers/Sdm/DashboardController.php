<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
	public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getDataDashboard(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $jumlah_karyawan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'";

            $jumlah_kesehatan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND bpjs = '1'";

            $jumlah_ketenagakerjaan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND no_bpjs != '-' OR no_bpjs != null";

            $jumlah_pria = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'
            AND jk = 'L'";

            $jumlah_wanita = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'
            AND jk = 'P'";

            $tingkat_pendidikan = "SELECT a.kode_strata, a.nama AS nama_strata, isnull(b.jumlah, 0) AS jumlah
            FROM hr_strata a
            LEFT JOIN (SELECT kode_strata, kode_lokasi, count(nik) AS jumlah
            FROM hr_pendidikan
            GROUP BY kode_strata, kode_lokasi
            ) b ON a.kode_strata=b.kode_strata AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."' AND a.kode_strata != '-'";

            $lokasi_kerja = "SELECT a.kode_loker, a.nama AS nama_loker, isnull(b.jumlah, 0) AS jumlah
            FROM hr_loker a
            LEFT JOIN (SELECT kode_loker, kode_lokasi, count(nik) AS jumlah
            FROM hr_karyawan
            GROUP BY kode_loker, kode_lokasi
            ) b ON a.kode_loker=b.kode_loker AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."' AND a.kode_loker != '-'";

            $selectJK = DB::connection($this->db)->select($jumlah_karyawan);
            $resJK = json_decode(json_encode($selectJK),true);

            $selectKes = DB::connection($this->db)->select($jumlah_kesehatan);
            $resKes = json_decode(json_encode($selectKes),true);

            $selectKer = DB::connection($this->db)->select($jumlah_ketenagakerjaan);
            $resKer = json_decode(json_encode($selectKer),true);

            $selectPria = DB::connection($this->db)->select($jumlah_pria);
            $resPria = json_decode(json_encode($selectPria),true);

            $selectWanita = DB::connection($this->db)->select($jumlah_wanita);
            $resWanita = json_decode(json_encode($selectWanita),true);
            
            $selectPend = DB::connection($this->db)->select($tingkat_pendidikan);
            $resPend = json_decode(json_encode($selectPend),true);

            $selectLok = DB::connection($this->db)->select($lokasi_kerja);
            $resLok = json_decode(json_encode($selectLok),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['jumlah_karyawan'] = $resJK;
            $success['jumlah_kesehatan'] = $resKes;
            $success['jumlah_kerja'] = $resKer;
            $success['jumlah_pria'] = $resPria;
            $success['jumlah_wanita'] = $resWanita;
            $success['tingkat_pendidikan'] = $resPend;
            $success['lokasi_kerja'] = $resLok;

            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }   
}
