<?php
namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardChartController extends Controller { 
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getKelompokGaji(Request $r) { 
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql1 = "exec [dbo].[sp_hr_dash] '202108','$kode_lokasi'";
            DB::connection($this->db)->update($sql1);

            $sql = "SELECT a.kode_klp, b.nama_klp, a.nilai FROM hr_dashklp_periode a
            INNER JOIN hr_dashklp b on a.kode_klp=b.kode_klp and b.kode_lokasi = '".$kode_lokasi."' 
            WHERE a.jenis_klp = 'GAJI' AND b.kode_lokasi = '".$kode_lokasi."' AND a.periode = '202108'";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $ctg = [];
            $data = [];

            if(count($rs) > 0) {
                foreach($rs as $dt) {
                    array_unshift($ctg, $dt['nama_klp']);
                    array_unshift($data, intval($dt['nilai']));
                }
            }
            

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'data' => $data
            ];

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKelompokUmur(Request $r) { 
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql1 = "exec [dbo].[sp_hr_dash] '202108','$kode_lokasi'";
            DB::connection($this->db)->update($sql1);

            $sql = "SELECT a.kode_klp, b.nama_klp, a.nilai FROM hr_dashklp_periode a
            INNER JOIN hr_dashklp b on a.kode_klp=b.kode_klp and b.kode_lokasi = '".$kode_lokasi."' 
            WHERE a.jenis_klp = 'UMUR' AND b.kode_lokasi = '".$kode_lokasi."' AND a.periode = '202108'";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $ctg = [];
            $data = [];

            if(count($rs) > 0) {
                foreach($rs as $dt) {
                    array_unshift($ctg, $dt['nama_klp']);
                    array_unshift($data, intval($dt['nilai']));
                }
            }
            

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'data' => $data
            ];

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getUnitCol(Request $r) { 
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT a.kode_unit, a.nama AS nama_unit, isnull(b.jumlah, 0) AS jumlah
            FROM hr_unit a
            LEFT JOIN (SELECT kode_unit, kode_lokasi, count(nik) AS jumlah
            FROM hr_karyawan
            GROUP BY kode_unit, kode_lokasi
            ) b ON a.kode_unit=b.kode_unit AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."'";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $data = [];
            $ctg = [];

            if(count($rs) > 0) {
                foreach($rs as $dt) {
                    $_data = [
                        'name' => $dt['nama_unit'],
                        'y' => intval($dt['jumlah']),
                        'kode' => $dt['kode_unit']
                    ];

                    array_unshift($ctg, $dt['nama_unit']);
                    array_unshift($data, $_data);
                }
            }
            

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'data' => $data
            ];

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getUnitPie(Request $r) { 
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT a.kode_unit, a.nama AS nama_unit, isnull(b.jumlah, 0) AS jumlah
            FROM hr_unit a
            LEFT JOIN (SELECT kode_unit, kode_lokasi, count(nik) AS jumlah
            FROM hr_karyawan
            GROUP BY kode_unit, kode_lokasi
            ) b ON a.kode_unit=b.kode_unit AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."'";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $total = 0;
            $series = [];

            if(count($rs) > 0) {
                foreach($rs as $dt) {
                    $total = $total + $dt['jumlah'];
                }

                foreach($rs as $dt) {
                    $persen = ($dt['jumlah'] / $total) * 100;
                    $_persen = floatval(number_format((float)$persen, 1, '.', ''));
                    $data = [
                        'name' => $dt['nama_unit'],
                        'y' => $_persen
                    ];

                    array_unshift($series, $data);
                }
            }
            

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $series;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPendidikan(Request $r) { 
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT a.kode_strata, a.nama AS nama_strata, isnull(b.jumlah, 0) AS jumlah
            FROM hr_strata a
            LEFT JOIN (SELECT kode_strata, kode_lokasi, count(nik) AS jumlah
            FROM hr_karyawan
            GROUP BY kode_strata, kode_lokasi
            ) b ON a.kode_strata=b.kode_strata AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."' ORDER BY a.nu";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);

            $ctg = [];
            $data = [];

            if(count($rs) > 0) {
                foreach($rs as $dt) {
                    array_unshift($ctg, $dt['kode_strata']);
                    array_unshift($data, intval($dt['jumlah']));
                }
            } else {
                array_unshift($ctg, "SD", "SMP", "SMA", "D3", "S1", "S2");
                array_unshift($data, 0, 0, 0, 0, 0, 0);
            }
            

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'data' => $data,
            ];

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}
?>