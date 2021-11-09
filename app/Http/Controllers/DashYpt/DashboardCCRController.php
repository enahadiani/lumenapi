<?php
namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardCCRController extends Controller {
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

    public function getDataBox(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi='20' AND a.kode_fs='FS1' AND a.kode_grafik in ('PI01','PI02','PI03','PI04')";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, 
            ISNULL(b.total,0) AS tn1, ISNULL(c.total,0) AS tn2, ISNULL(b.total,0) + ISNULL(c.total,0) AS tn3,
			ISNULL(d.total,0) AS pn1, ISNULL(e.total,0) AS pn2, ISNULL(d.total,0) + ISNULL(e.total,0) AS pn3,
			ISNULL(f.total,0) - ISNULL(g.total,0) AS piutang, 
			ISNULL(h.total,0) AS hn1, ISNULL(i.total,0) AS hn2, ISNULL(h.total,0) + ISNULL(i.total,0) AS hn3
			FROM lokasi a
			LEFT JOIN (
                SELECT x.kode_lokasi,
				SUM(CASE WHEN x.dc='D' THEN x.nilai ELSE -x.nilai END) AS total 
				FROM sis_bill_d x 
				INNER JOIN sis_siswa y ON x.nis=y.nis AND x.kode_lokasi=y.kode_lokasi AND x.kode_pp=y.kode_pp 
				WHERE x.kode_lokasi='12' AND (x.periode BETWEEN '202101' AND '202109')
				GROUP BY x.kode_lokasi
			) b ON a.kode_lokasi=b.kode_lokasi 
			LEFT JOIN (
                SELECT x.kode_lokasi, SUM(CASE WHEN x.dc='D' THEN x.nilai ELSE -x.nilai END) AS total 
				FROM sis_bill_d x 
				INNER JOIN sis_siswa y ON x.nis=y.nis AND x.kode_lokasi=y.kode_lokasi AND x.kode_pp=y.kode_pp 
				WHERE x.kode_lokasi='12' AND x.periode='202109'
				GROUP BY x.kode_lokasi
			) c ON a.kode_lokasi=c.kode_lokasi 
			LEFT JOIN (
                SELECT x.kode_lokasi, SUM(CASE WHEN x.dc='D' THEN x.nilai ELSE -x.nilai END) AS total 
				FROM sis_rekon_d x 
				INNER JOIN sis_siswa y ON x.nis=y.nis AND x.kode_lokasi=y.kode_lokasi AND x.kode_pp=y.kode_pp 
				WHERE x.kode_lokasi='12' AND (x.periode BETWEEN '202101' AND '202108') 
                AND (x.periode_bill BETWEEN '202101' AND '202108') 
				GROUP BY x.kode_lokasi
			) d ON a.kode_lokasi=d.kode_lokasi 
			LEFT JOIN (
                SELECT x.kode_lokasi, SUM(CASE WHEN x.dc='D' THEN x.nilai ELSE -x.nilai END) AS total 
				FROM sis_rekon_d x 
				INNER JOIN sis_siswa y ON x.nis=y.nis AND x.kode_lokasi=y.kode_lokasi AND x.kode_pp=y.kode_pp 
				WHERE x.kode_lokasi='12' AND x.periode='202109' AND x.periode_bill = '202109' 
				GROUP BY x.kode_lokasi
			) e ON a.kode_lokasi=e.kode_lokasi 
			LEFT JOIN (
                SELECT x.kode_lokasi, SUM(CASE WHEN x.dc='D' THEN x.nilai ELSE -x.nilai END) AS total 
				FROM sis_bill_d x 
				INNER JOIN sis_siswa y ON x.nis=y.nis AND x.kode_lokasi=y.kode_lokasi AND x.kode_pp=y.kode_pp 
				WHERE x.kode_lokasi='12' AND x.periode<'202101'
				GROUP BY x.kode_lokasi
			) f ON a.kode_lokasi=f.kode_lokasi 
			LEFT JOIN (
                SELECT x.kode_lokasi, SUM(CASE WHEN x.dc='D' THEN x.nilai ELSE -x.nilai END) AS total 
				FROM sis_rekon_d x 
				INNER JOIN sis_siswa y ON x.nis=y.nis AND x.kode_lokasi=y.kode_lokasi AND x.kode_pp=y.kode_pp 
				WHERE x.kode_lokasi='12' and x.periode<'202101' 
			    GROUP BY x.kode_lokasi
			) g ON a.kode_lokasi=g.kode_lokasi 
			LEFT JOIN (SELECT x.kode_lokasi, SUM(CASE WHEN x.dc='D' THEN x.nilai ELSE -x.nilai END) AS total 
				from sis_rekon_d x 
				INNER JOIN sis_siswa y ON x.nis=y.nis AND x.kode_lokasi=y.kode_lokasi AND x.kode_pp=y.kode_pp 
				WHERE x.kode_lokasi='12' AND (x.periode BETWEEN '202101' AND '202108') AND (x.periode_bill<'202101') 
				GROUP BY x.kode_lokasi
			) h ON a.kode_lokasi=h.kode_lokasi 
			LEFT JOIN (
                SELECT x.kode_lokasi, SUM(CASE WHEN x.dc='D' THEN x.nilai ELSE -x.nilai END) AS total 
				FROM sis_rekon_d x 
				INNER JOIN sis_siswa y ON x.nis=y.nis AND x.kode_lokasi=y.kode_lokasi AND x.kode_pp=y.kode_pp 
				WHERE x.kode_lokasi='12' AND x.periode='202109' AND (x.periode_bill<'202101') 
				GROUP BY x.kode_lokasi
			) i ON a.kode_lokasi=i.kode_lokasi 
			WHERE a.kode_lokasi='12'";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $ccr_total = ((floatval($res[0]['pn3']) + floatval($res[0]['hn3'])) / (floatval($res[0]['pn3']) + floatval($res[0]['hn3'])))*100;
            $ccr_tahun_lalu = (floatval($res[0]['hn3']) / floatval($res[0]['piutang']))*100;
            $ccr_tahun_ini = (floatval($res[0]['pn2']) / floatval($res[0]['tn2']))*100;
            $ccr_periode = (floatval($res[0]['pn3']) / floatval($res[0]['tn3']))*100;

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "ccr_total" => [
                    'persentase' => floatval(number_format((float)$ccr_total, 1, '.', ''))
                ],
                "ccr_tahun_lalu" => [
                    'persentase' => floatval(number_format((float)$ccr_tahun_lalu, 1, '.', ''))
                ],
                "ccr_tahun_ini" => [
                    'persentase' => floatval(number_format((float)$ccr_tahun_ini, 1, '.', ''))
                ],
                "ccr_periode" => [
                    'persentase' => floatval(number_format((float)$ccr_periode, 1, '.', ''))
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