<?php
namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardDetailClientController extends Controller { 
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getDataClient(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT client, fungsi, ISNULL(alamat, '-') AS alamat, count(*) AS jumlah
			FROM hr_karyawan 
            WHERE kode_lokasi = '".$kode_lokasi."'
            GROUP BY client, fungsi, alamat";

            $res = DB::connection($this->db)->select($res);
            $res = json_decode(json_encode($res),true);

            
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

    public function getDataClientPie(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT client,count(*) AS jumlah 
            FROM hr_karyawan 
            WHERE kode_lokasi = '".$kode_lokasi."'
            GROUP BY client";

            $res = DB::connection($this->db)->select($res);
            $res = json_decode(json_encode($res),true);

            $total = 0;
            for($i=0;$i<count($res);$i++) {
                $total = $total + floatval($res[$i]['jumlah']);
            }

            $series = array();
            for($i=0;$i<count($res);$i++) {
                $percentage = (floatval($res[$i]['jumlah']) / $total) * 100;
                $_percentage = floatval(number_format((float)$percentage, 1, '.', ''));
                $data = [
                    'name' => $res[$i]['client'],
                    'y' => $_percentage
                ];
                array_push($series, $data);
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
}
?>