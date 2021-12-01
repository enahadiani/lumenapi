<?php
namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardCFController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $sql = 'sqlsrvyptkug';

    private function filterReq($request,$col_array,$db_col_name,$where,$this_in){
        for($i = 0; $i<count($col_array); $i++){
            if(ISSET($request->input($col_array[$i])[0])){
                if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                    $where .= " AND (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                }elseif($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                }elseif($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                    $tmp = explode(",",$request->input($col_array[$i])[1]);
                    $this_in = "";
                    for($x=0;$x<count($tmp);$x++){
                        if($x == 0){
                            $this_in .= "'".$tmp[$x]."'";
                        }ELSE{
        
                            $this_in .= ","."'".$tmp[$x]."'";
                        }
                    }
                    $where .= " AND ".$db_col_name[$i]." in ($this_in) ";
                }elseif($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                }elseif($request->input($col_array[$i])[0] == "<>" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." <> '".$request->input($col_array[$i])[1]."' ";
                }
            }
        }
        return $where;
    }

    public function getCashFlowBulanan(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, ISNULL(b.n3,0) AS n3,
            ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, ISNULL(b.n6,0) AS n6, ISNULL(b.n7,0) AS n7, 
            ISNULL(b.n8,0) AS n8, ISNULL(b.n9,0) AS n9, ISNULL(b.n10,0) AS n10, ISNULL(b.n11,0) AS n11, 
            ISNULL(b.n12,0) AS n12
            FROM lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN b.n4 ELSE 0 END) AS n1,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN b.n4 ELSE 0 END) AS n2,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN b.n4 ELSE 0 END) AS n3,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN b.n4 ELSE 0 END) AS n4,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN b.n4 ELSE 0 END) AS n5,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN b.n4 ELSE 0 END) AS n6,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN b.n4 ELSE 0 END) AS n7,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN b.n4 ELSE 0 END) AS n8,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN b.n4 ELSE 0 END) AS n9,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN b.n4 ELSE 0 END) AS n10,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN b.n4 ELSE 0 END) AS n11,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN b.n4 ELSE 0 END) AS n12
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                WHERE a.kode_lokasi='20' AND a.kode_fs='FS1'  AND a.kode_grafik IN ('PI08') AND SUBSTRING(b.periode,1,4)='2021'
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi='20'";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $ctg = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGT', 'SEP', 'OKT', 'NOV', 'DES'];
            $nilaiCI = [];

            foreach($res as $dt) {
                array_push($nilaiCI, 
                floatval($dt['n1']), 
                floatval($dt['n2']), 
                floatval($dt['n3']), 
                floatval($dt['n4']), 
                floatval($dt['n5']), 
                floatval($dt['n6']), 
                floatval($dt['n7']), 
                floatval($dt['n8']), 
                floatval($dt['n9']), 
                floatval($dt['n10']), 
                floatval($dt['n11']), 
                floatval($dt['n12']));
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'cash_in' => $nilaiCI
            ];
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBox(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql = "SELECT a.kode_lokasi, SUM(debet) AS debet, SUM(kredit) AS kredit, 
            SUM(debet) - SUM(kredit) AS mutasi, SUM(so_akhir) AS so_akhir
            FROM exs_glma a
            INNER JOIN flag_relasi b ON a.kode_akun=b.kode_akun AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi='20' AND a.periode='202109' AND b.kode_flag IN ('001','009') 
            GROUP BY a.kode_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'inflow' => [
                    'nominal' => floatval($res[0]['debet'])
                ],
                'outflow' => [
                    'nominal' => floatval($res[0]['kredit'])
                ],
                'cash_balance' => [
                    'nominal' => floatval($res[0]['mutasi'])
                ],
                'closing' => [
                    'nominal' => floatval($res[0]['so_akhir'])
                ],
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