<?php 
namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardBoxController extends Controller {
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getJumlahJenisKelamin(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'
            AND jk = 'L'";

            $rs1 = DB::connection($this->db)->select($sql);
            $rs1 = json_decode(json_encode($rs1),true);

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'
            AND jk = 'P'";

            $rs2 = DB::connection($this->db)->select($sql);
            $rs2 = json_decode(json_encode($rs2),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'pria' => intval($rs1[0]['jumlah']),
                'perempuan' => intval($rs2[0]['jumlah']),
            ];

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getClient(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT client, count(*) AS jumlah 
            FROM hr_karyawan 
            WHERE kode_lokasi = '".$kode_lokasi."'
            GROUP BY client";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $rs;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBPJSKerja(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> '-' AND no_bpjs_kerja <> '')";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = intval($rs[0]['jumlah']);

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBPJSSehat(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs IS NOT NULL AND no_bpjs <> '-' AND no_bpjs <> '')";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = intval($rs[0]['jumlah']);

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPegawai(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = intval($rs[0]['jumlah']);

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}

?>