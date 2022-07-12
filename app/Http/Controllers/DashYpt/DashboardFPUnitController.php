<?php
namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardFPUnitController extends Controller {

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
                        }else{
        
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

    /**
     * Function ini untuk API data box pertama
     * Pendapatan, Beban, SHU, dan OR
     * Data yang diberikan berupa nilai real, persentase Ach, nilai YoY, dan persentase YoY
     * 
     */
    public function getDataBoxFirst(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $lokasi = $r->kode_lokasi;
            }else{
                $lokasi = $kode_lokasi;
            }

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = "";
            }
            
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == 'GB'){
                    $filter_bidang = " and p.kode_bidang in ('4','5') ";
                }else{
                    $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
                }
            }else{
                $filter_bidang = " ";
            }

            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                    $n2 = "n7";
                    $n5 = "n9";
                }else{
                    $n4 = "n4";
                    $n2 = "n2";
                    $n5 = "n5";
                }
            }else{
                $n4 = "n4";
                $n2 = "n2";
                $n5 = "n5";
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi='$lokasi' AND a.kode_fs='FS1' AND a.kode_grafik in ('PI01','PI02','PI03','PI04')";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_grafik, c.nama,
            SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
            SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
            SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5
            FROM dash_ypt_grafik_d a
            INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
            INNER JOIN pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
            INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
            $where $filter_bidang $filter_pp
            group by a.kode_grafik, c.nama
            order by a.kode_grafik ";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $data = array();
            foreach($res as $item) {
                $yoy = ($item['n5'] != 0 ? (($item['n4']-$item['n5'])/abs($item['n5']))*100 : 0);
                if($item['kode_grafik'] == 'PI04'){
                    try{
                        $ach = ($item['n2']/$item['n4'])*100;
                    }catch(\Throwable $e){
                        $ach = 0;
                    }
                }else{
                    try{
                        $ach = ($item['n2'] < 0 ? (1+($item['n2']-$item['n4'])/$item['n2'])*100 : (1+($item['n4']-$item['n2'])/$item['n2'])*100 );
                    }catch(\Throwable $e){
                        $ach = 0;
                    }
                }
                array_push($data,
                [
                    "kode_grafik" => $item['kode_grafik'],
                    "nama" => $item['nama'],
                    "n2" => floatval(number_format((float)$item['n2'], 2,'.', '')),
                    "n4" => floatval(number_format((float)$item['n4'], 2,'.', '')),
                    "n5" => floatval(number_format((float)$item['n5'], 2,'.', '')),
                    "ach" => floatval(number_format((float)$ach, 2,'.', '')),
                    "yoy" => floatval(number_format((float)$yoy, 2,'.', '')),
                ]);
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "data_pdpt" => $data[0],
                "data_beban" => $data[1],
                "data_shu" => $data[2],
                "data_or" => $data[3],
                "lokasi" => $kode_lokasi
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getLabaRugi(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = "";
            }
            
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == 'GB'){
                    $filter_bidang = " and p.kode_bidang in ('4','5') ";
                }else{
                    $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
                }
            }else{
                $filter_bidang = " ";
            }
           
            $tahun = substr($r->periode[1],0,4);
            $where = "WHERE a.kode_lokasi in ('$kode_lokasi') AND a.kode_fs='FS1' and substring(b.periode,1,4)='$tahun' $filter_bidang $filter_pp ";

            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }

            $sql = "SELECT a.kode_grafik, a.nama, 
            ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, 
            ISNULL(b.n3,0) AS n3, ISNULL(b.n4,0) AS n4, 
            ISNULL(b.n5,0) AS n5, ISNULL(b.n6,0) AS n6, 
            ISNULL(b.n7,0) AS n7, ISNULL(b.n8,0) AS n8, 
            ISNULL(b.n9,0) AS n9, ISNULL(b.n10,0) AS n10, 
            ISNULL(b.n11,0) AS n11, ISNULL(b.n12,0) AS n12
            FROM dash_ypt_grafik_m a
            LEFT JOIN (SELECT a.kode_grafik,a.kode_lokasi,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n1,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n2,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n3,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n4,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n5,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n6,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n7,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n8,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n9,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n10,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n11,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS n12		
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
				INNER JOIN pp p ON b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_grafik,a.kode_lokasi
            ) b ON a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi='$kode_lokasi' and a.kode_grafik IN ('PI01','PI02','PI03')";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $ctg = ['JAN','FEB','MAR','APR','MEI','JUN','JUL','AGU','SEP','OKT','NOV','DES'];
            $series = [];
            $i=0;
            $color = ['#b91c1c','#064E3B','#FBBF24'];
            foreach($res as $item) { 
                $data = [];
                for($c=1; $c <= 12; $c++){
                    $d = floatval(number_format((float)$item['n'.$c], 0, '.', ''));
                    array_push($data, $d);
                }
                $series[$i] = array(
                    'name' => $item['nama'],
                    'color' => $color[$i],
                    'key' => $item['kode_grafik'],
                    'data' => $data
                );
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

    public function getSaldoKasBank(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode=$r->periode[1];
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == 'GB'){
                    $filter_bidang = " and p.kode_bidang in ('4','5') ";
                }else{
                    $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
                }
            }else{
                $filter_bidang = " ";
            }

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = "";
            }

            $sql = "select a.kode_akun,b.nama, sum(a.so_akhir) as so_akhir 
            from exs_glma_pp a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.periode='$periode' and a.kode_lokasi='$kode_lokasi' $filter_bidang $filter_pp and (b.nama like 'Kas%' or b.nama like 'Bank%')
            group by a.kode_akun,b.nama
            having sum(a.so_akhir) <> 0 ";

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