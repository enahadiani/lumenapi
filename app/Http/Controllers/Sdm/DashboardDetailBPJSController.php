<?php
namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardDetailBPJSController extends Controller { 
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getKomposisiBPJSKerja(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql1 = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'";

            $sql2 = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> '-' AND no_bpjs_kerja <> '')";

            $sql3 = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs_kerja IS NULL OR no_bpjs_kerja = '-' OR no_bpjs_kerja = '')";

            $sql4 = "SELECT nik, nama, no_bpjs_kerja AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> ''
            AND no_bpjs_kerja <> '-'";

            $sql5 = "SELECT nik, nama, isnull(no_bpjs_kerja, '-') AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND (no_bpjs_kerja IS NULL OR no_bpjs_kerja = '' OR no_bpjs_kerja = '-')";

            $res1 = DB::connection($this->db)->select($sql1);
            $res1 = json_decode(json_encode($res1),true);

            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            $res5 = DB::connection($this->db)->select($sql5);
            $res5 = json_decode(json_encode($res5),true);

            $jumlah_karyawan = floatval($res1[0]['jumlah']);
            $jumlah_bpjs = floatval($res2[0]['jumlah']);
            $jumlah_non_bpjs = floatval($res3[0]['jumlah']);

            $percentage_terdaftar = ($jumlah_bpjs / $jumlah_karyawan) * 100;
            $_percentage_terdaftar = floatval(number_format((float)$percentage_terdaftar, 1, '.', ''));
            $percentage_non_terdaftar = ($jumlah_non_bpjs / $jumlah_karyawan) * 100;
            $_percentage_non_terdaftar = floatval(number_format((float)$percentage_non_terdaftar, 1, '.', ''));

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'terdaftar' => $res4,
                'nonterdaftar' => $res5,
                'total' => $jumlah_karyawan,
                'qty_terdaftar' => $jumlah_bpjs,
                'qty_nonterdaftar' => $jumlah_non_bpjs,
                'percent_terdaftar' => $_percentage_terdaftar,
                'percent_nonterdaftar' => $_percentage_non_terdaftar
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKomposisiBPJSSehat(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql1 = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'";

            $sql2 = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs IS NOT NULL AND no_bpjs <> '-' AND no_bpjs <> '')";

            $sql3 = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs IS NULL OR no_bpjs = '-' OR no_bpjs = '')";

            $sql4 = "SELECT nik, nama, no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND no_bpjs IS NOT NULL AND no_bpjs <> '' AND no_bpjs <> '-'";

            $sql5 = "SELECT nik, nama, isnull(no_bpjs, '-') AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND (no_bpjs IS NULL OR no_bpjs = '' OR no_bpjs = '-')";

            $res1 = DB::connection($this->db)->select($sql1);
            $res1 = json_decode(json_encode($res1),true);

            $res2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $res4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            $res5 = DB::connection($this->db)->select($sql5);
            $res5 = json_decode(json_encode($res5),true);

            $jumlah_karyawan = floatval($res1[0]['jumlah']);
            $jumlah_bpjs = floatval($res2[0]['jumlah']);
            $jumlah_non_bpjs = floatval($res3[0]['jumlah']);

            $percentage_terdaftar = ($jumlah_bpjs / $jumlah_karyawan) * 100;
            $_percentage_terdaftar = floatval(number_format((float)$percentage_terdaftar, 1, '.', ''));
            $percentage_non_terdaftar = ($jumlah_non_bpjs / $jumlah_karyawan) * 100;
            $_percentage_non_terdaftar = floatval(number_format((float)$percentage_non_terdaftar, 1, '.', ''));

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'terdaftar' => $res4,
                'nonterdaftar' => $res5,
                'total' => $jumlah_karyawan,
                'qty_terdaftar' => $jumlah_bpjs,
                'qty_nonterdaftar' => $jumlah_non_bpjs,
                'percent_terdaftar' => $_percentage_terdaftar,
                'percent_nonterdaftar' => $_percentage_non_terdaftar
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBPJSKerjaUnRegister(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $where = "";
            if($r->query('bpjs') != null && $r->query('bpjs') != '') {
                $where = " AND no_bpjs_kerja LIKE '%".$r->query('bpjs')."%'";
            }

            $sql = "SELECT nik, nama, ISNULL(no_bpjs_kerja, '-') AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND (no_bpjs_kerja IS NULL OR no_bpjs_kerja = '-' OR no_bpjs_kerja = '') 
            $where";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBPJSKerjaRegister(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $where = "";
            if($r->query('bpjs') != null && $r->query('bpjs') != '') {
                $where = " AND no_bpjs_kerja LIKE '%".$r->query('bpjs')."%'";
            } else {
                $where = " AND no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> '' AND no_bpjs_kerja <> '-'";
            }

            $sql = "SELECT nik, nama, no_bpjs_kerja AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' $where";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBPJSSehatUnRegister(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $where = "";
            if($r->query('bpjs') != null && $r->query('bpjs') != '') {
                $where = " AND no_bpjs LIKE '%".$r->query('bpjs')."%'";
            }

            $sql = "SELECT nik, nama, no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND (no_bpjs IS NULL OR no_bpjs = '-' OR no_bpjs = '') $where";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBPJSSehatRegister(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $where = "";
            if($r->query('bpjs') != null && $r->query('bpjs') != '') {
                $where = " AND no_bpjs LIKE '%".$r->query('bpjs')."%'";
            } else {
                $where = " AND no_bpjs IS NOT NULL AND no_bpjs <> '' AND no_bpjs <> '-'";
            }

            $sql = "SELECT nik, nama, no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' $where";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}
?>