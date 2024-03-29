<?php
namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardFPV2Controller extends Controller {

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
            INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
            INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
            $where
            group by a.kode_grafik, c.nama
            order by a.kode_grafik ";

            $select = DB::connection($this->sql)->select($sql);
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

    public function getDataOR(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $filter_lokasi = " and a.kode_lokasi = '$r->kode_lokasi' ";
            }else{
                $filter_lokasi = "";
            }
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik in ('PI04') $filter_lokasi ";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, 
            ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.capai,0) as capai,a.skode
            FROM dash_ypt_lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n2 ELSE b.n2 END) AS n2,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('11','12','13','14','15') $filter_lokasi ";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $anggaran = [];
            $realisasi = [];
            $ctg = [];
            foreach($res as $item) {  

                $name= $item['skode'];

                array_push($ctg, $name);
                array_push($anggaran, floatval(number_format((float)$item['n2'], 2,'.', '')));
                array_push($realisasi, floatval(number_format((float)$item['n4'], 2,'.', '')));
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'anggaran' => $anggaran,
                'realisasi' => $realisasi,
                'lokasi' => $filter_lokasi
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataSHU(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $filter_lokasi = " and a.kode_lokasi = '$r->kode_lokasi' ";
            }else{
                $filter_lokasi = "";
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik in ('PI03') $filter_lokasi";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, 
            ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.capai,0) as capai,a.skode
            FROM dash_ypt_lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n2 ELSE b.n2 END) AS n2,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('11','12','13','14','15') $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $anggaran = [];
            $realisasi = [];
            $ctg = [];
            foreach($res as $item) {  
                $name = $item['skode'];

                array_push($ctg, $name);
                array_push($anggaran, floatval($item['n2']));
                array_push($realisasi, floatval($item['n4']));
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'anggaran' => $anggaran,
                'realisasi' => $realisasi,
                'lokasi' => $filter_lokasi
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBeban(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $filter_lokasi = " and a.kode_lokasi = '$r->kode_lokasi' ";
            }else{
                $filter_lokasi = "";
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI02') $filter_lokasi";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, 
            ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.capai,0) as capai,a.skode
            FROM dash_ypt_lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n2 ELSE b.n2 END) AS n2,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('11','12','13','14','15') $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $anggaran = [];
            $realisasi = [];
            $ctg = [];
            foreach($res as $item) {  
                $name = $item['skode'];

                array_push($ctg, $name);
                array_push($anggaran, floatval($item['n2']));
                array_push($realisasi, floatval($item['n4']));
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'anggaran' => $anggaran,
                'realisasi' => $realisasi,
                'lokasi' => $filter_lokasi
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataPdpt(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $filter_lokasi = " and a.kode_lokasi = '$r->kode_lokasi' ";
            }else{
                $filter_lokasi = "";
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI01') $filter_lokasi";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, 
            ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.capai,0) as capai,a.skode
            FROM dash_ypt_lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n2 ELSE b.n2 END) AS n2,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('11','12','13','14','15') $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $anggaran = [];
            $realisasi = [];
            $ctg = [];
            foreach($res as $item) {  
                $name = $item['skode'];

                array_push($ctg, $name);
                array_push($anggaran, floatval($item['n2']));
                array_push($realisasi, floatval($item['n4']));
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'anggaran' => $anggaran,
                'realisasi' => $realisasi,
                'lokasi' => $r->kode_lokasi
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