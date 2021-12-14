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
    public $db = 'sqlsrvyptkug';

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
            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $kode_lokasi = $r->kode_lokasi;
            }
            
            $where = "WHERE b.kode_lokasi='$kode_lokasi' AND a.kode_fs='FS1'  AND a.kode_grafik IN ('PI08')";
            $tahun = substr($r->periode[1],0,4);
            $tahunseb = intval($tahun)-1;
            $sql = "SELECT a.kode_lokasi, b.nama,b.jenis, ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, ISNULL(b.n3,0) AS n3,
            ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, ISNULL(b.n6,0) AS n6, ISNULL(b.n7,0) AS n7, 
            ISNULL(b.n8,0) AS n8, ISNULL(b.n9,0) AS n9, ISNULL(b.n10,0) AS n10, ISNULL(b.n11,0) AS n11, 
            ISNULL(b.n12,0) AS n12
            FROM dash_ypt_lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,'sa' as jenis, 'Saldo' as nama,
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
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where AND SUBSTRING(b.periode,1,4)='$tahun'
                GROUP BY a.kode_lokasi
                union all
                SELECT a.kode_lokasi,'ci' as jenis, 'Cash In' as nama,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN b.n15 ELSE 0 END) AS n1,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN b.n15 ELSE 0 END) AS n2,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN b.n15 ELSE 0 END) AS n3,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN b.n15 ELSE 0 END) AS n4,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN b.n15 ELSE 0 END) AS n5,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN b.n15 ELSE 0 END) AS n6,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN b.n15 ELSE 0 END) AS n7,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN b.n15 ELSE 0 END) AS n8,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN b.n15 ELSE 0 END) AS n9,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN b.n15 ELSE 0 END) AS n10,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN b.n15 ELSE 0 END) AS n11,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN b.n15 ELSE 0 END) AS n12
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where AND SUBSTRING(b.periode,1,4)='$tahun'
                GROUP BY a.kode_lokasi
			  union all
                SELECT a.kode_lokasi,'co' as jenis, 'Cash Out' as nama,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN b.n16 ELSE 0 END) AS n1,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN b.n16 ELSE 0 END) AS n2,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN b.n16 ELSE 0 END) AS n3,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN b.n16 ELSE 0 END) AS n4,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN b.n16 ELSE 0 END) AS n5,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN b.n16 ELSE 0 END) AS n6,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN b.n16 ELSE 0 END) AS n7,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN b.n16 ELSE 0 END) AS n8,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN b.n16 ELSE 0 END) AS n9,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN b.n16 ELSE 0 END) AS n10,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN b.n16 ELSE 0 END) AS n11,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN b.n16 ELSE 0 END) AS n12
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where AND SUBSTRING(b.periode,1,4)='$tahun'
                GROUP BY a.kode_lokasi
                union all
                SELECT a.kode_lokasi,'sa_yoy' as jenis,  'Saldo YoY' as nama,
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
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where AND SUBSTRING(b.periode,1,4)='$tahunseb'
                GROUP BY a.kode_lokasi
                union all
                SELECT a.kode_lokasi,'ci_yoy' as jenis, 'Cash In YoY' as nama,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN b.n15 ELSE 0 END) AS n1,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN b.n15 ELSE 0 END) AS n2,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN b.n15 ELSE 0 END) AS n3,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN b.n15 ELSE 0 END) AS n4,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN b.n15 ELSE 0 END) AS n5,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN b.n15 ELSE 0 END) AS n6,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN b.n15 ELSE 0 END) AS n7,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN b.n15 ELSE 0 END) AS n8,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN b.n15 ELSE 0 END) AS n9,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN b.n15 ELSE 0 END) AS n10,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN b.n15 ELSE 0 END) AS n11,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN b.n15 ELSE 0 END) AS n12
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where AND SUBSTRING(b.periode,1,4)='$tahunseb'
                GROUP BY a.kode_lokasi
			  union all
                SELECT a.kode_lokasi,'co_yoy' as jenis, 'Cash Out' as nama,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN b.n16 ELSE 0 END) AS n1,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN b.n16 ELSE 0 END) AS n2,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN b.n16 ELSE 0 END) AS n3,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN b.n16 ELSE 0 END) AS n4,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN b.n16 ELSE 0 END) AS n5,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN b.n16 ELSE 0 END) AS n6,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN b.n16 ELSE 0 END) AS n7,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN b.n16 ELSE 0 END) AS n8,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN b.n16 ELSE 0 END) AS n9,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN b.n16 ELSE 0 END) AS n10,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN b.n16 ELSE 0 END) AS n11,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN b.n16 ELSE 0 END) AS n12
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where AND SUBSTRING(b.periode,1,4)='$tahunseb'
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            
            WHERE a.kode_lokasi='$kode_lokasi' ";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $ctg = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGT', 'SEP', 'OKT', 'NOV', 'DES'];
            $series = array();
            $colors = ['#8085E9','#90ED7D','#F7A35C','#7CB5EC','#059669','#434348'];
            $i=0;
            foreach($res as $dt) {
                if(!isset($series[$i])){
                    $series[$i] = array(
                        'name' => $dt['nama'],
                        'data' => array(),
                        'color' => $colors[$i]
                    );
                }
                $data = array(
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
                $series[$i]['data'] = $data;
                $i++;
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'series' => $series
            ];
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
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
            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $kode_lokasi = $r->kode_lokasi;
            }
            if($r->periode[0] == 'range'){
                $periode = $r->periode[2];
                $tahun = substr($periode,0,4);
                $periodeawal = $tahun.'01';
                $tahunseb = intval($tahun)-1;
                $bln = substr($periode,4,2);
                $blnseb = intval($bln)-1;
                $periodeseb = $tahunseb.$bln;
                $periodeawalseb = $tahunseb.'01';
                $periode2 = $tahun.$blnseb;
                $blnseb = (strlen(strval($blnseb)) > 1) ? $blnseb : "0".$blnseb;
                $filter_periode = " and b.periode between '$periodeawal' and '$periode' ";
                $filter_periode2 = " and b.periode between '$periodeawalseb' and '$periodeseb' ";
                $filter_periode3 = " and b.periode between '$periode2awal' and '$periode2' ";
            }else{
                $periode = $r->periode[1];
                $tahun = substr($periode,0,4);
                $tahunseb = intval($tahun)-1;
                $bln = substr($periode,4,2);
                $blnseb = intval($bln)-1;
                $periodeseb = $tahunseb.$bln;
                $blnseb = (strlen(strval($blnseb)) > 1) ? $blnseb : "0".$blnseb;
                $periode2 = $tahun.$blnseb;
                $filter_periode = " and b.periode='$periode' ";
                $filter_periode2 = " and b.periode='$periodeseb' ";
                $filter_periode3 = " and b.periode='$periode2' ";
            }
            $sql = "select a.kode_lokasi,isnull(b.so_akhir,0) as so_akhir,isnull(b.debet,0) as debet,isnull(b.kredit,0) as kredit,isnull(b.mutasi,0) as mutasi,
            isnull(c.so_akhir,0) as so_akhir_rev,isnull(c.debet,0) as debet_rev,isnull(c.kredit,0) as kredit_rev,isnull(c.mutasi,0) as mutasi_rev,
            isnull(d.so_akhir,0) as so_akhir_mom,isnull(d.debet,0) as debet_mom,isnull(d.kredit,0) as kredit_mom,isnull(d.mutasi,0) as mutasi_mom
            from dash_ypt_lokasi a
            left join (select a.kode_lokasi,sum(b.n4) as so_akhir,sum(b.n15) as debet,sum(b.n16) as kredit,sum(b.n10) as mutasi
            from dash_ypt_grafik_d a
            inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
            where a.kode_grafik='PI08' and b.kode_lokasi='$kode_lokasi' $filter_periode
            group by a.kode_lokasi
                    )b on a.kode_lokasi=b.kode_lokasi
            left join (select a.kode_lokasi,sum(b.n4) as so_akhir,sum(b.n15) as debet,sum(b.n16) as kredit,sum(b.n10) as mutasi
            from dash_ypt_grafik_d a
            inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
            where a.kode_grafik='PI08' and b.kode_lokasi='$kode_lokasi' $filter_periode2
            group by a.kode_lokasi
                    )c on a.kode_lokasi=c.kode_lokasi  	
            left join (select a.kode_lokasi,sum(b.n4) as so_akhir,sum(b.n15) as debet,sum(b.n16) as kredit,sum(b.n10) as mutasi
                    from dash_ypt_grafik_d a
                    inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                    where a.kode_grafik='PI08' and b.kode_lokasi='$kode_lokasi' $filter_periode3
                    group by a.kode_lokasi
                    )d on a.kode_lokasi=d.kode_lokasi  		
            where a.kode_lokasi='$kode_lokasi'";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'inflow' => [
                    'nominal' => floatval($res[0]['debet']),
                    'yoy' => floatval($res[0]['debet_rev']),
                    'mom' => floatval($res[0]['debet_mom'])
                ],
                'outflow' => [
                    'nominal' => floatval($res[0]['kredit']),
                    'yoy' => floatval($res[0]['kredit_rev']),
                    'mom' => floatval($res[0]['kredit_mom'])
                ],
                'cash_balance' => [
                    'nominal' => floatval($res[0]['mutasi']),
                    'yoy' => floatval($res[0]['mutasi_rev']),
                    'mom' => floatval($res[0]['mutasi_mom'])
                ],
                'closing' => [
                    'nominal' => floatval($res[0]['so_akhir']),
                    'yoy' => floatval($res[0]['so_akhir_rev']),
                    'mom' => floatval($res[0]['so_akhir_mom'])
                ],
                'periode' => $periode,
                'sql' => $sql
            ];
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getSoAkhirPerLembaga(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode=$r->periode[1];
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_grafik='PI08' ";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");
            $sql = "select a.kode_lokasi,a.nama,a.skode,isnull(b.so_akhir,0) as so_akhir
            from dash_ypt_lokasi a
            left join (select a.kode_lokasi,sum(b.n4) as so_akhir
                    from dash_ypt_grafik_d a
                    inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                    $where and a.kode_fs='FS1'
                    group by a.kode_lokasi
            )b on a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi in ('11','12','13','14','15','20')
            ";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}
?>