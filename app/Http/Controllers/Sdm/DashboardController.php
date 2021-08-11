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

    public function getDataKaryawan(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $where = "where a.kode_lokasi = '".$kode_lokasi."'";

            if($request->input('pendidikan') === null) {
                $filter_array = array('jk','kode_loker');
                $col_array = array('a.jk', 'a.kode_loker');

                for($i=0;$i<count($col_array);$i++) {
                    if($request->input($filter_array[$i]) !== null) {
                        $where .= " AND ".$col_array[$i]." = '".$request->input($filter_array[$i])."'";
                    }
                }

                $select = "SELECT a.nik, a.nama AS nama_pegawai, b.nama AS nama_jabatan, a.no_telp, a.email
                FROM hr_karyawan a
                INNER JOIN hr_jab b ON a.kode_jab=b.kode_jab AND a.kode_lokasi=b.kode_lokasi
                $where";

                $res = DB::connection($this->db)->select($select);
                $res = json_decode(json_encode($res),true);
            } else {
                $select = "SELECT a.nik, a.nama AS nama_pegawai, b.nama AS nama_jabatan, a.no_telp, a.email
                FROM hr_karyawan a
                INNER JOIN hr_jab b ON a.kode_jab=b.kode_jab AND a.kode_lokasi=b.kode_lokasi
                INNER JOIN hr_pendidikan c ON a.nik=c.nik AND a.kode_lokasi=c.kode_lokasi
                $where AND c.kode_strata = '".$request->input('pendidikan')."'";

                $res = DB::connection($this->db)->select($select);
                $res = json_decode(json_encode($res),true);
            }

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";     
            }
            else{
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

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

            $jabatan = "SELECT a.kode_jab, a.nama AS nama_jabatan, isnull(b.jumlah, 0) AS jumlah
            FROM hr_jab a
            LEFT JOIN (SELECT kode_jab, kode_lokasi, count(nik) AS jumlah
            FROM hr_karyawan
            GROUP BY kode_jab, kode_lokasi
            ) b ON a.kode_jab=b.kode_jab AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."' AND a.kode_jab != '-'";

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

            $selectJab = DB::connection($this->db)->select($jabatan);
            $resJab = json_decode(json_encode($selectJab),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['jumlah_karyawan'] = $resJK;
            $success['jumlah_kesehatan'] = $resKes;
            $success['jumlah_kerja'] = $resKer;
            $success['jumlah_pria'] = $resPria;
            $success['jumlah_wanita'] = $resWanita;
            $success['tingkat_pendidikan'] = $resPend;
            $success['lokasi_kerja'] = $resLok;
            $success['jabatan'] = $resJab;

            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }   
}
