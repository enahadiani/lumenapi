<?php

namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardFPController extends Controller
{
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

// SUDAH TDK DIGUNAKAN
    /**
     * Function ini untuk API data box pertama
     * Pendapatan, Beban, SHU, dan OR
     * Data yang diberikan berupa nilai real, persentase Ach, nilai YoY, dan persentase YoY
     * 
     */
    // public function getDataBoxFirst(Request $r) {
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
    //         $col_array = array('periode');
    //         $db_col_name = array('b.periode');
    //         $where = "WHERE a.kode_lokasi='20' AND a.kode_fs='FS1' AND a.kode_grafik in ('PI01','PI02','PI03','PI04')";
    //         $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

    //         $sql = "SELECT a.kode_grafik, c.nama,
    //         CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END AS n1,
    //         CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n2 ELSE b.n2 END AS n2,
    //         CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END AS n4,
    //         CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END AS n5,
    //         CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END AS capai,
    //         CASE ISNULL(b.n2,0) WHEN 0 THEN 0 ELSE (b.n4/b.n2)*100 END AS ach,
    //         CASE ISNULL(b.n4,0) WHEN 0 THEN 0 ELSE ((b.n4 - b.n5)/b.n5)*100 END AS yoy
    //         FROM dash_ypt_grafik_d a
    //         INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
    //         INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
    //         $where";

    //         $select = DB::connection($this->sql)->select($sql);
    //         $res = json_decode(json_encode($select),true);

    //         $data_pdpt = array();
    //         $data_beban = array();
    //         $data_shu = array();
    //         $data_or = array();
    //         foreach($res as $item) {
    //             if($item['kode_grafik'] == 'PI01') {
    //                 $data_pdpt = [
    //                     "kode_grafik" => $item['kode_grafik'],
    //                     "nama" => $item['nama'],
    //                     "n1" => floatval(number_format((float)$item['n1'], 2,'.', '')),
    //                     "n2" => floatval(number_format((float)$item['n2'], 2,'.', '')),
    //                     "n4" => floatval(number_format((float)$item['n4'], 2,'.', '')),
    //                     "n5" => floatval(number_format((float)$item['n5'], 2,'.', '')),
    //                     "capai" => floatval(number_format((float)$item['capai'], 2,'.', '')),
    //                     "ach" => floatval(number_format((float)$item['ach'], 2,'.', '')),
    //                     "yoy" => floatval(number_format((float)$item['yoy'], 2,'.', '')),
    //                 ];
    //             } elseif($item['kode_grafik'] == 'PI02') {
    //                 $data_beban = [
    //                     "kode_grafik" => $item['kode_grafik'],
    //                     "nama" => $item['nama'],
    //                     "n1" => floatval(number_format((float)$item['n1'], 2,'.', '')),
    //                     "n2" => floatval(number_format((float)$item['n2'], 2,'.', '')),
    //                     "n4" => floatval(number_format((float)$item['n4'], 2,'.', '')),
    //                     "n5" => floatval(number_format((float)$item['n5'], 2,'.', '')),
    //                     "capai" => floatval(number_format((float)$item['capai'], 2,'.', '')),
    //                     "ach" => floatval(number_format((float)$item['ach'], 2,'.', '')),
    //                     "yoy" => floatval(number_format((float)$item['yoy'], 2,'.', '')),
    //                 ];
    //             } elseif($item['kode_grafik'] == 'PI03') {
    //                 $data_shu = [
    //                     "kode_grafik" => $item['kode_grafik'],
    //                     "nama" => $item['nama'],
    //                     "n1" => floatval(number_format((float)$item['n1'], 2,'.', '')),
    //                     "n2" => floatval(number_format((float)$item['n2'], 2,'.', '')),
    //                     "n4" => floatval(number_format((float)$item['n4'], 2,'.', '')),
    //                     "n5" => floatval(number_format((float)$item['n5'], 2,'.', '')),
    //                     "capai" => floatval(number_format((float)$item['capai'], 2,'.', '')),
    //                     "ach" => floatval(number_format((float)$item['ach'], 2,'.', '')),
    //                     "yoy" => floatval(number_format((float)$item['yoy'], 2,'.', '')),
    //                 ];
    //             } elseif($item['kode_grafik'] == 'PI04') {
    //                 $data_or = [
    //                     "kode_grafik" => $item['kode_grafik'],
    //                     "nama" => $item['nama'],
    //                     "n1" => floatval(number_format((float)$item['n1'], 2,'.', '')),
    //                     "n2" => floatval(number_format((float)$item['n2'], 2,'.', '')),
    //                     "n4" => floatval(number_format((float)$item['n4'], 2,'.', '')),
    //                     "n5" => floatval(number_format((float)$item['n5'], 2,'.', '')),
    //                     "capai" => floatval(number_format((float)$item['capai'], 2,'.', '')),
    //                     "ach" => floatval(number_format((float)$item['ach'], 2,'.', '')),
    //                     "yoy" => floatval(number_format((float)$item['yoy'], 2,'.', '')),
    //                 ];
    //             }
    //         }

    //         $success['status'] = true;
    //         $success['message'] = "Success!";
    //         $success['data'] = [
    //             "data_pdpt" => $data_pdpt,
    //             "data_beban" => $data_beban,
    //             "data_shu" => $data_shu,
    //             "data_or" => $data_or
    //         ];

    //         return response()->json($success, $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
    // }

    /**
     * Function ini untuk API data box Pendapatan
     * 
     */
    // public function getDataBoxPdpt(Request $r) {
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
    //         $col_array = array('periode');
    //         $db_col_name = array('b.periode');
    //         $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI01')";
    //         $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

    //         $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
    //         ISNULL(b.capai,0) as capai,a.skode
    //         FROM dash_ypt_lokasi a
    //         LEFT JOIN (
    //             SELECT a.kode_lokasi,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
    //             SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
    //             FROM dash_ypt_grafik_d a
    //             INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
    //             INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
    //             $where
    //             GROUP BY a.kode_lokasi
    //         ) b ON a.kode_lokasi=b.kode_lokasi
    //         WHERE a.kode_lokasi IN ('11','12','13','14','15')";

    //         $select = DB::connection($this->sql)->select($sql);
    //         $res = json_decode(json_encode($select),true);
            
    //         $total = 0;
    //         foreach($res as $item) {
    //             $total = $total + floatval($item['n4']);
    //         }

    //         $chart = [];
    //         if($total > 0) {
    //             foreach($res as $item) { 
    //                 $persen = (floatval($item['n4']) / $total) * 100;
    //                 $_persen = number_format((float)$persen, 2,'.', '');
    //                 $name = $item['skode'];

    //                 if($_persen < 0) {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => abs(floatval($_persen)),
    //                         'negative' => true
    //                     ];
    //                 } else {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => floatval($_persen),
    //                         'negative' => false
    //                     ];
    //                 }
    //                 array_push($chart, $value);
    //             }
    //         } elseif($total < 0) {
    //             foreach($res as $item) { 
    //                 $persen = (floatval($item['n4']) / $total) * 100;
    //                 $_persen = number_format((float)$persen, 2,'.', '');
                    
    //                 $name = $item['skode'];

    //                 if($_persen < 0) {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => abs(floatval($_persen)),
    //                         'negative' => true
    //                     ];
    //                 } else {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => floatval($_persen),
    //                         'negative' => false
    //                     ];
    //                 }
    //                 array_push($chart, $value);
    //             }
    //         } else {
    //             foreach($res as $item) {
    //                 $_persen = 0;
    //                 $name = $item['skode'];

    //                 $value = [
    //                     'name' => $name,
    //                     'y' => floatval($_persen),
    //                     'negative' => false
    //                 ];
    //                 array_push($chart, $value);
    //             }
    //         }

    //         $success['status'] = true;
    //         $success['message'] = "Success!";
    //         $success['data'] = $chart;

    //         return response()->json($success, $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
    // }

    /**
     * Function ini untuk API data box beban
     */
    // public function getDataBoxBeban(Request $r) {
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
    //         $col_array = array('periode');
    //         $db_col_name = array('b.periode');
    //         $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI02')";
    //         $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

    //         $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
    //         ISNULL(b.capai,0) as capai,a.skode
    //         FROM dash_ypt_lokasi a
    //         LEFT JOIN (
    //             SELECT a.kode_lokasi,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
    //             SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
    //             FROM dash_ypt_grafik_d a
    //             INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
    //             INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
    //             $where
    //             GROUP BY a.kode_lokasi
    //         ) b ON a.kode_lokasi=b.kode_lokasi
    //         WHERE a.kode_lokasi IN ('11','12','13','14','15')";

    //         $select = DB::connection($this->sql)->select($sql);
    //         $res = json_decode(json_encode($select),true);
            
    //         $total = 0;
    //         foreach($res as $item) {
    //             $total = $total + floatval($item['n4']);
    //         }

    //         $chart = [];
    //         if($total > 0) {
    //             foreach($res as $item) { 
    //                 $persen = (floatval($item['n4']) / $total) * 100;
    //                 $_persen = number_format((float)$persen, 2,'.', '');
    //                 $name = $item['skode'];

    //                 if($_persen < 0) {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => abs(floatval($_persen)),
    //                         'negative' => true
    //                     ];
    //                 } else {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => floatval($_persen),
    //                         'negative' => false
    //                     ];
    //                 }
    //                 array_push($chart, $value);
    //             }
    //         } elseif($total < 0) {
    //             foreach($res as $item) { 
    //                 $persen = (floatval($item['n4']) / $total) * 100;
    //                 $_persen = number_format((float)$persen, 2,'.', '');
                    
    //                 $name = $item['skode'];

    //                 if($_persen < 0) {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => abs(floatval($_persen)),
    //                         'negative' => true
    //                     ];
    //                 } else {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => floatval($_persen),
    //                         'negative' => false
    //                     ];
    //                 }
    //             }
    //         } else {
    //             foreach($res as $item) {
    //                 $_persen = 0;
                    
    //                 $name = $item['skode'];

    //                 $value = [
    //                     'name' => $name,
    //                     'y' => floatval($_persen),
    //                     'negative' => false
    //                 ];
    //                 array_push($chart, $value);
    //             }
    //         }

    //         $success['status'] = true;
    //         $success['message'] = "Success!";
    //         $success['data'] = $chart;

    //         return response()->json($success, $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
    // }

    /**
     * Function ini untuk API data box SHU
     */
    // public function getDataBoxShu(Request $r) {
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
    //         $col_array = array('periode');
    //         $db_col_name = array('b.periode');
    //         $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik in ('PI03')";
    //         $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

    //         $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5,
    //         ISNULL(b.capai,0) AS capai,a.skode
    //         FROM dash_ypt_lokasi a
    //         LEFT JOIN (
    //             SELECT a.kode_lokasi,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
    //             SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
    //             FROM dash_ypt_grafik_d a
    //             INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
    //             INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
    //             $where
    //             GROUP BY a.kode_lokasi
    //         ) b ON a.kode_lokasi=b.kode_lokasi
    //         WHERE a.kode_lokasi IN ('11','12','13','14','15')";

    //         $select = DB::connection($this->sql)->select($sql);
    //         $res = json_decode(json_encode($select),true);
            
    //         $total = 0;
    //         foreach($res as $item) {
    //             $total = $total + floatval($item['n4']);
    //         }

    //         $chart = [];
    //         if($total > 0) {
    //             foreach($res as $item) { 
    //                 $persen = (floatval($item['n4']) / $total) * 100;
    //                 $_persen = number_format((float)$persen, 2,'.', '');
                    
                    
    //                 $name = $item['skode'];

    //                 if($_persen < 0) {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => abs(floatval($_persen)),
    //                         'negative' => true,
    //                         'nilai' => $item['n4']
    //                     ];
    //                 } else {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => floatval($_persen),
    //                         'negative' => false,
    //                         'nilai' => $item['n4']
    //                     ];
    //                 }
    //                 array_push($chart, $value);
    //             }
    //         } elseif($total < 0) {
    //             foreach($res as $item) { 
    //                 $persen = (floatval($item['n4']) / $total) * 100;
    //                 $_persen = number_format((float)$persen, 2,'.', '');
                    
    //                 $name = $item['skode'];

    //                 if($_persen < 0) {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => abs(floatval($_persen)),
    //                         'negative' => true,
    //                         'nilai' => $item['n4']
    //                     ];
    //                 } else {
    //                     $value = [
    //                         'name' => $name,
    //                         'y' => floatval($_persen),
    //                         'negative' => false,
    //                         'nilai' => $item['n4']
    //                     ];
    //                 }
    //                 array_push($chart, $value);
    //             }
    //         } else {
    //             foreach($res as $item) {
    //                 $_persen = 0;
    //                 $name = $item['skode'];
    //                 $value = [
    //                     'name' => $name,
    //                     'y' => floatval($_persen),
    //                     'negative' => false,
    //                     'nilai' => $item['n4']
    //                 ];
    //                 array_push($chart, $value);
    //             }
    //         }

    //         $success['status'] = true;
    //         $success['message'] = "Success!";
    //         $success['data'] = $chart;

    //         return response()->json($success, $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['data'] = [];
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
    // }

    /**
     * Function ini untuk API data box OR
     */
    // public function getDataBoxOr(Request $r) {
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
    //         $col_array = array('periode');
    //         $db_col_name = array('b.periode');
    //         $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik in ('PI04')";
    //         $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

    //         $sql = "SELECT a.kode_lokasi,a.nama, ISNULL(b.n1,0) as n1, ISNULL(b.n4,0) as n4, ISNULL(b.n5,0) as n5, 
    //         ISNULL(b.capai,0) as capai,a.skode
    //         FROM dash_ypt_lokasi a
    //         LEFT JOIN (SELECT a.kode_lokasi,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
    //             SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
    //             SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
    //             FROM dash_ypt_grafik_d a
    //             INNER JOIN exs_neraca b on a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
    //             INNER JOIN dash_ypt_grafik_m c on a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
    //             $where
    //             GROUP BY a.kode_lokasi
    //         ) b ON a.kode_lokasi=b.kode_lokasi
    //         WHERE a.kode_lokasi IN ('11','12','13','14','15')";

    //         $select = DB::connection($this->sql)->select($sql);
    //         $res = json_decode(json_encode($select),true);
            
    //         $total = 0;
    //         foreach($res as $item) {
    //             $total = $total + floatval(abs($item['n4']));
    //         }

    //         $chart = [];
    //         if($total > 0) {
    //             foreach($res as $item) { 
    //                 $persen = (floatval(abs($item['n4'])) / $total) * 100;
    //                 $_persen = number_format((float)$persen, 2,'.', '');
                    
    //                 $name = $item['skode'];

    //                 $value = [
    //                     'name' => $name,
    //                     'y' => floatval($_persen)
    //                 ];
    //                 array_push($chart, $value);
    //             }
    //         } else {
    //             foreach($res as $item) {
    //                 $_persen = 0;

    //                 $name = $item['skode'];

    //                 $value = [
    //                     'name' => $name,
    //                     'y' => floatval($_persen)
    //                 ];
    //                 array_push($chart, $value);
    //             }
    //         }

    //         $success['status'] = true;
    //         $success['message'] = "Success!";
    //         $success['data'] = $chart;

    //         return response()->json($success, $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
    // }
// 

    /**
     * Function ini untuk API data box laba rugi
     */
    public function getDataBoxLabaRugi(Request $r) {
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
            $where = "WHERE a.kode_lokasi in ('01') AND a.kode_fs='FS1' $filter_lokasi";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }

            $sql = "SELECT a.kode_pp as kode_lokasi, a.nama, ISNULL(b.pdpt,0) AS pdpt, ISNULL(b.beban,0) AS beban, 
            ISNULL(b.shu,0) AS shu,a.skode
            FROM dash_ypt_pp a
            LEFT JOIN (SELECT a.kode_lokasi,b.kode_pp,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS pdpt,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS beban,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS shu		
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi,b.kode_pp
            ) b ON a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            WHERE a.kode_lokasi IN ('01') $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $ctg = [];
            $data_pdpt = [];
            $data_beban = [];
            $data_shu = [];
            foreach($res as $item) { 
                $pdpt = floatval(number_format((float)$item['pdpt'], 0, '.', ''));
                $beban = floatval(number_format((float)$item['beban'], 0, '.', ''));
                $shu = floatval(number_format((float)$item['shu'], 0, '.', ''));

                $name = $item['skode'];

                array_push($ctg, $name);
                array_push($data_pdpt, $pdpt);
                array_push($data_beban, $beban);
                array_push($data_shu, $shu);
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'data_pdpt' => $data_pdpt,
                'data_beban' => $data_beban,
                'data_shu' => $data_shu,
                'lokasi' => $filter_lokasi
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API data box performansi lembaga
     */
    public function getDataBoxPerformLembaga(Request $r) {
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
            $where = "WHERE a.kode_lokasi in ('01') AND a.kode_fs='FS1' $filter_lokasi";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

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

            $sql = "SELECT a.kode_pp as kode_lokasi, a.nama, a.skode,
            case when ISNULL(b.pdpt_n2,0) <> 0 then (ISNULL(b.pdpt_n4,0)/ISNULL(b.pdpt_n2,0))*100 else 0 end AS pdpt_ach, 
            case when ISNULL(b.pdpt_n5,0) <> 0 then ((ISNULL(b.pdpt_n4,0)-ISNULL(b.pdpt_n5,0))/ISNULL(b.pdpt_n5,0))*100 else 0 end AS pdpt_yoy,
            case when ISNULL(b.beban_n2,0) <> 0 then (ISNULL(b.beban_n4,0)/ISNULL(b.beban_n2,0))*100 else 0 end AS beban_ach, 
            case when ISNULL(b.beban_n5,0) <> 0 then ((ISNULL(b.beban_n4,0)-ISNULL(b.beban_n5,0))/ISNULL(b.beban_n5,0))*100 else 0 end AS beban_yoy,
            case when ISNULL(b.shu_n2,0) <> 0 then (ISNULL(b.shu_n4,0)/ISNULL(b.shu_n2,0))*100 else 0 end AS shu_ach, 
            case when ISNULL(b.shu_n5,0) <> 0 then ((ISNULL(b.shu_n4,0)-ISNULL(b.shu_n5,0))/ISNULL(b.shu_n5,0))*100 else 0 end AS shu_yoy,
            case when ISNULL(b.or_n4,0) <> 0 then (ISNULL(b.or_n2,0)/ISNULL(b.or_n4,0))*100 else 0 end AS or_ach, 
            case when ISNULL(b.or_n5,0) <> 0 then ((ISNULL(b.or_n4,0)-ISNULL(b.or_n5,0))/ISNULL(b.or_n5,0))*100 else 0 end AS or_yoy
                        from dash_ypt_pp a
                        LEFT JOIN (SELECT a.kode_lokasi,b.kode_pp,
                            SUM(CASE WHEN a.kode_grafik='PI01' THEN b.$n4 ELSE 0 END) AS pdpt_n4,
                            SUM(CASE WHEN a.kode_grafik='PI01' THEN b.$n2 ELSE 0 END) AS pdpt_n2,
                            SUM(CASE WHEN a.kode_grafik='PI01' THEN b.$n5 ELSE 0 END) AS pdpt_n5,
                            SUM(CASE WHEN a.kode_grafik='PI02' THEN b.$n4 ELSE 0 END) AS beban_n4,
                            SUM(CASE WHEN a.kode_grafik='PI02' THEN b.$n2 ELSE 0 END) AS beban_n2,
                            SUM(CASE WHEN a.kode_grafik='PI02' THEN b.$n5 ELSE 0 END) AS beban_n5,
                            SUM(CASE WHEN a.kode_grafik='PI03' THEN b.$n4 ELSE 0 END) AS shu_n4,
                            SUM(CASE WHEN a.kode_grafik='PI03' THEN b.$n2 ELSE 0 END) AS shu_n2,
                            SUM(CASE WHEN a.kode_grafik='PI03' THEN b.$n5 ELSE 0 END) AS shu_n5,
                            SUM(CASE WHEN a.kode_grafik='PI04' THEN b.$n4 ELSE 0 END) AS or_n4,
                            SUM(CASE WHEN a.kode_grafik='PI04' THEN b.$n2 ELSE 0 END) AS or_n2,
                            SUM(CASE WHEN a.kode_grafik='PI04' THEN b.$n5 ELSE 0 END) AS or_n5
                            FROM dash_ypt_grafik_d a
                            INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                            INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                            $where $filter_lokasi
                            GROUP BY a.kode_lokasi,b.kode_pp
                        ) b on a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                        WHERE a.kode_lokasi IN ('01') $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $data_perform = [];
            foreach($res as $item) { 
                
                $name = $item['skode'];
                $perform = [
                    "kode_lokasi" => $item['kode_lokasi'],
                    "nama" => $name,
                    "pdpt_ach" => floatval(number_format((float)$item['pdpt_ach'], 2,'.', '')),
                    "pdpt_yoy" => floatval(number_format((float)$item['pdpt_yoy'], 2,'.', '')),
                    "beban_ach" => floatval(number_format((float)$item['beban_ach'], 2,'.', '')),
                    "beban_yoy" => floatval(number_format((float)$item['beban_yoy'], 2,'.', '')),
                    "shu_ach" => floatval(number_format((float)$item['shu_ach'], 2,'.', '')),
                    "shu_yoy" => floatval(number_format((float)$item['shu_yoy'], 2,'.', '')),
                    "or_ach" => floatval(number_format((float)$item['or_ach'], 2,'.', '')),
                    "or_yoy" => floatval(number_format((float)$item['or_yoy'], 0, '.', '')),
                ];
                array_push($data_perform, $perform);
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $data_perform;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }   
    }

    /**
     * Function ini untuk API detail performansi lembaga
     * 
     */
    public function getDataPerformansiLembaga(Request $r) {
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

            $col_array = array('periode', 'kode_grafik');
            $db_col_name = array('b.periode', 'a.kode_grafik');
            $where = "WHERE a.kode_fs = 'FS1' $filter_lokasi";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

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

            if(isset($r->kode_grafik) && $r->kode_grafik[1] == "PI04"){

                $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n2,0) AS n2, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
                ISNULL(b.capai,0) as capai,a.skode
                FROM dash_ypt_lokasi a
                LEFT JOIN (
                    SELECT a.kode_lokasi,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5,
                    CASE WHEN sum(b.$n4)<>0 THEN (sum(b.$n2)/sum(b.$n4))*100 ELSE 0 END AS capai
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    $where
                    GROUP BY a.kode_lokasi
                ) b ON a.kode_lokasi=b.kode_lokasi
                WHERE a.kode_lokasi IN ('11','12','13','14','15') $filter_lokasi";
            }else{
                $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n2,0) AS n2, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
                ISNULL(b.capai,0) as capai,a.skode
                FROM dash_ypt_lokasi a
                LEFT JOIN (
                    SELECT a.kode_lokasi,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5,
                    CASE WHEN sum(b.$n2)<>0 THEN (sum(b.$n4)/sum(b.$n2))*100 ELSE 0 END AS capai
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    $where
                    GROUP BY a.kode_lokasi
                ) b ON a.kode_lokasi=b.kode_lokasi
                WHERE a.kode_lokasi IN ('11','12','13','14','15') $filter_lokasi";
            }

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $ctg = [];
            $data_realisasi = [];
            $data_anggaran = [];
            foreach($res as $item) {
                
                $name = $item['skode'];

                $realisasi = floatval(number_format((float)$item['capai'], 2,'.', ''));
                $sisa = 100 - $realisasi;
                $anggaran = 100;
                
                array_push($ctg, $name);
                array_push($data_realisasi, $realisasi);
                array_push($data_anggaran, $anggaran);
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "kategori" => $ctg,
                "realisasi" => $data_realisasi,
                "anggaran" => $data_anggaran
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API detail per lembaga
     * 
     */
    public function getDataPerLembaga(Request $r) {
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
            $col_array = array('periode','kode_grafik');
            $db_col_name = array('b.periode','a.kode_grafik');
            $where = "WHERE a.kode_lokasi in ('11','12','13','14','15') AND a.kode_fs='FS1' $filter_lokasi ";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

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

            if(isset($r->kode_grafik) && $r->kode_grafik[1] == "PI04"){
                $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n2,0) AS n2, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
                ISNULL(b.capai,0) as capai,a.skode
                FROM dash_ypt_lokasi a
                LEFT JOIN (
                    SELECT a.kode_lokasi,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5,
                    CASE WHEN sum(b.$n4)<>0 THEN (sum(b.$n2)/sum(b.$n4))*100 ELSE 0 END AS capai
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    $where
                    GROUP BY a.kode_lokasi
                ) b ON a.kode_lokasi=b.kode_lokasi
                WHERE a.kode_lokasi IN ('11','12','13','14','15') $filter_lokasi";
            }else{
                $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n2,0) AS n2, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
                ISNULL(b.capai,0) as capai,a.skode
                FROM dash_ypt_lokasi a
                LEFT JOIN (
                    SELECT a.kode_lokasi,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5,
                    CASE WHEN sum(b.$n2)<>0 THEN (sum(b.$n4)/sum(b.$n2))*100 ELSE 0 END AS capai
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    $where
                    GROUP BY a.kode_lokasi
                ) b ON a.kode_lokasi=b.kode_lokasi
                WHERE a.kode_lokasi IN ('11','12','13','14','15') $filter_lokasi";
            }

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $total = 0;
            foreach($res as $item) {
                $total = $total + floatval(abs($item['n4']));
            }

            $chart = [];
            $idx = 0;
            if($total > 0) {
                foreach($res as $item) { 
                    $persen = (floatval($item['n4']) / $total) * 100;
                    $_persen = number_format((float)$persen, 2,'.', '');
                    
                    $name = $item['skode'];

                    if($idx == 0) {
                        if($_persen < 0){
                            $value = [
                                'name' => $name,
                                'y' => abs(floatval($_persen)),
                                'sliced' =>  true,
                                'selected' => true,
                                'negative' => true,
                                'fillColor' => 'url(#custom-pattern)',                            
                                'color' => 'url(#custom-pattern)',
                                'nilai' => $item['n4']
                            ];
                        }else{

                            $value = [
                                'name' => $name,
                                'y' => floatval($_persen),
                                'sliced' =>  true,
                                'selected' => true,
                                'negative' => false,
                                'nilai' => $item['n4']
                            ];
                        }
                    } else {
                        if($_persen < 0){
                            $value = [
                                'name' => $name,
                                'y' => abs(floatval($_persen)),
                                'negative' => true,
                                'fillColor' => 'url(#custom-pattern)',                            
                                'color' => 'url(#custom-pattern)',
                                'nilai' => $item['n4']
                            ];
                        }else{
                            $value = [
                                'name' => $name,
                                'y' => floatval($_persen),'negative' => false,
                                'nilai' => $item['n4']
                            ];
                        }
                    }
                    array_push($chart, $value);
                    $idx++;
                }
            } else {
                foreach($res as $item) {
                    $_persen = 0;
                    $name = $item['skode'];
                    if($_persen < 0) {
                        $value = [
                            'name' => $name,
                            'y' => abs(floatval($_persen)),
                            'negative' => true,
                            'fillColor' => 'url(#custom-pattern)',
                            'color' => 'url(#custom-pattern)',
                            'nilai' => $item['n4']
                        ];
                    }else{
                        $value = [
                            'name' => $name,
                            'y' => floatval($_persen),
                            'negative' => false,
                            'nilai' => $item['n4']
                        ];
                    }
                    array_push($chart, $value);
                }
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $chart;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API detail kelompok Yoy
     * 
     */
    public function getDataKelompokYoyBackup(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            // $col_array = array('kode_grafik');
            // $db_col_name = array('a.kode_grafik');
            if($r->query('kode_grafik')[1] == "PI01") {
                $kode_grafik = "PI05";
            } elseif($r->query('kode_grafik')[1] == "PI02") {
                $kode_grafik = "PI06";
            } else {
                $kode_grafik = $r->query('kode_grafik')[1];
            }

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $lokasi = $r->kode_lokasi;
            }else{
                $lokasi = $kode_lokasi;
            }

            $where = "WHERE a.kode_grafik = '".$kode_grafik."' and a.kode_fs='FS1' ";

            $tahun = intval($r->query('periode')[1]);
            $periode = [];
            for($i=0;$i<=5;$i++) {
                if($i == 0) {
                    array_push($periode, $tahun);
                } else {
                    $tahun = $tahun - 100;
                    array_push($periode, $tahun);
                }
            }

            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }

            $sql = "SELECT DISTINCT a.kode_neraca,UPPER(a.nama) as nama, ISNULL(b.n3,0) AS n3, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.n6,0) AS n6
            FROM neraca a
            INNER JOIN (
                SELECT a.kode_neraca,
                    SUM(CASE WHEN b.jenis_akun <> 'Pendapatan' THEN ISNULL(b.$n4,0) ELSE -ISNULL(b.$n4,0) END) AS n1,
                    SUM(CASE WHEN c.jenis_akun <> 'Pendapatan' THEN ISNULL(c.$n4,0) ELSE -ISNULL(c.$n4,0) END) AS n2,
                    SUM(CASE WHEN d.jenis_akun <> 'Pendapatan' THEN ISNULL(d.$n4,0) ELSE -ISNULL(d.$n4,0) END) AS n3,
                    SUM(CASE WHEN e.jenis_akun <> 'Pendapatan' THEN ISNULL(e.$n4,0) ELSE -ISNULL(e.$n4,0) END) AS n4,
                    SUM(CASE WHEN f.jenis_akun <> 'Pendapatan' THEN ISNULL(f.$n4,0) ELSE -ISNULL(f.$n4,0) END) AS n5,
                    SUM(CASE WHEN g.jenis_akun <> 'Pendapatan' THEN ISNULL(g.$n4,0) ELSE -ISNULL(g.$n4,0) END) AS n6
                    FROM dash_ypt_grafik_d a
                    INNER JOIN dash_ypt_grafik_m x ON a.kode_grafik=x.kode_grafik AND a.kode_lokasi=x.kode_lokasi
                    LEFT JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND b.kode_lokasi='$lokasi' AND a.kode_fs=b.kode_fs AND b.periode='".$periode[5]."'
                    LEFT JOIN exs_neraca c ON a.kode_neraca=c.kode_neraca AND c.kode_lokasi='$lokasi' AND a.kode_fs=c.kode_fs AND c.periode='".$periode[4]."'
                    LEFT JOIN exs_neraca d ON a.kode_neraca=d.kode_neraca AND d.kode_lokasi='$lokasi' AND a.kode_fs=d.kode_fs AND d.periode='".$periode[3]."'
                    LEFT JOIN exs_neraca e ON a.kode_neraca=e.kode_neraca AND e.kode_lokasi='$lokasi' AND a.kode_fs=e.kode_fs AND e.periode='".$periode[2]."'
                    LEFT JOIN exs_neraca f ON a.kode_neraca=f.kode_neraca AND f.kode_lokasi='$lokasi' AND a.kode_fs=f.kode_fs AND f.periode='".$periode[1]."'
                    LEFT JOIN exs_neraca g ON a.kode_neraca=g.kode_neraca AND g.kode_lokasi='$lokasi' AND a.kode_fs=g.kode_fs AND g.periode='".$periode[0]."'
                    $where
                GROUP BY a.kode_neraca
            )b ON a.kode_neraca=b.kode_neraca 
            where a.kode_lokasi='$lokasi' AND LEN(a.kode_neraca) = '3' and a.kode_fs='FS1' ";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            // $color = ['#1D4ED8', '#EC4899', '#EC4899'];
            $ctg = [];
            for($i=0;$i<=3;$i++) {
                array_unshift($ctg, substr($periode[$i], 0, 4));
            }
            
            $series = [];
            foreach($res as $item) {
                $data = [];
                array_unshift($data, floatval($item['n3']), floatval($item['n4']), floatval($item['n5']), floatval($item['n6']));

                $_series = [
                    'name' => $item['nama'],
                    'data' => $data
                ];

                array_push($series, $_series);
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataKelompokYoy(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            // $col_array = array('kode_grafik');
            // $db_col_name = array('a.kode_grafik');
            $kode_grafik1 = $r->query('kode_grafik')[1];
            if($r->query('kode_grafik')[1] == "PI01") {
                $kode_grafik2 = "PI05";
            } elseif($r->query('kode_grafik')[1] == "PI02") {
                $kode_grafik2 = "PI06";
            } else {
                $kode_grafik2 = $r->query('kode_grafik')[1];
            }

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $lokasi = $r->kode_lokasi;
            }else{
                $lokasi = $kode_lokasi;
            }

            $where = "WHERE a.kode_grafik = '".$kode_grafik2."' and a.kode_fs='FS1' ";

            $tahun = intval($r->query('periode')[1]);
            $periode = [];
            for($i=0;$i<=5;$i++) {
                if($i == 0) {
                    array_push($periode, $tahun);
                } else {
                    $tahun = $tahun - 100;
                    array_push($periode, $tahun);
                }
            }

            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }

            $sql = "SELECT DISTINCT a.kode_neraca,UPPER(a.nama) as nama, ISNULL(b.n3,0) AS n3, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.n6,0) AS n6
            FROM neraca a
            INNER JOIN (
                SELECT a.kode_neraca,
                    SUM(CASE WHEN b.jenis_akun <> 'Pendapatan' THEN ISNULL(b.$n4,0) ELSE -ISNULL(b.$n4,0) END) AS n1,
                    SUM(CASE WHEN c.jenis_akun <> 'Pendapatan' THEN ISNULL(c.$n4,0) ELSE -ISNULL(c.$n4,0) END) AS n2,
                    SUM(CASE WHEN d.jenis_akun <> 'Pendapatan' THEN ISNULL(d.$n4,0) ELSE -ISNULL(d.$n4,0) END) AS n3,
                    SUM(CASE WHEN e.jenis_akun <> 'Pendapatan' THEN ISNULL(e.$n4,0) ELSE -ISNULL(e.$n4,0) END) AS n4,
                    SUM(CASE WHEN f.jenis_akun <> 'Pendapatan' THEN ISNULL(f.$n4,0) ELSE -ISNULL(f.$n4,0) END) AS n5,
                    SUM(CASE WHEN g.jenis_akun <> 'Pendapatan' THEN ISNULL(g.$n4,0) ELSE -ISNULL(g.$n4,0) END) AS n6
                    FROM dash_ypt_grafik_d a
                    INNER JOIN dash_ypt_grafik_m x ON a.kode_grafik=x.kode_grafik AND a.kode_lokasi=x.kode_lokasi
                    LEFT JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND b.kode_lokasi='$lokasi' AND a.kode_fs=b.kode_fs AND b.periode='".$periode[5]."'
                    LEFT JOIN exs_neraca c ON a.kode_neraca=c.kode_neraca AND c.kode_lokasi='$lokasi' AND a.kode_fs=c.kode_fs AND c.periode='".$periode[4]."'
                    LEFT JOIN exs_neraca d ON a.kode_neraca=d.kode_neraca AND d.kode_lokasi='$lokasi' AND a.kode_fs=d.kode_fs AND d.periode='".$periode[3]."'
                    LEFT JOIN exs_neraca e ON a.kode_neraca=e.kode_neraca AND e.kode_lokasi='$lokasi' AND a.kode_fs=e.kode_fs AND e.periode='".$periode[2]."'
                    LEFT JOIN exs_neraca f ON a.kode_neraca=f.kode_neraca AND f.kode_lokasi='$lokasi' AND a.kode_fs=f.kode_fs AND f.periode='".$periode[1]."'
                    LEFT JOIN exs_neraca g ON a.kode_neraca=g.kode_neraca AND g.kode_lokasi='$lokasi' AND a.kode_fs=g.kode_fs AND g.periode='".$periode[0]."'
                    $where
                GROUP BY a.kode_neraca
            )b ON a.kode_neraca=b.kode_neraca 
            where a.kode_lokasi='$lokasi' AND LEN(a.kode_neraca) = '3' and a.kode_fs='FS1' ";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            // $color = ['#1D4ED8', '#EC4899', '#EC4899'];
            $ctg = [];
            for($i=0;$i<=3;$i++) {
                array_unshift($ctg, substr($periode[$i], 0, 4));
            }
            
            $series = [];
            $drill = [];
            $n3=0;$n4=0;$n5=0;$n6=0; $i=0;
            $color =  ["#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1","#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1","#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1","#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1","#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"];
            foreach($res as $item) {
                $n3+= floatval($item['n3']);
                $n4+= floatval($item['n4']);
                $n5+= floatval($item['n5']);
                $n6+= floatval($item['n6']);

                $dd = [];
                array_unshift($dd, floatval($item['n3']), floatval($item['n4']), floatval($item['n5']), floatval($item['n6']));

                $_drill = [
                    'name' => $item['nama'],
                    'color' => $color[$i],
                    'data' => $dd
                ];

                array_push($drill, $_drill);
                $i++;
            }
            $data = [];
            $nama = 'Total';
            array_unshift($data, 
                array(
                    'y' =>floatval($n3),
                    'name' => $nama,
                    'drilldown' => $ctg[0]
                ), 
                array(
                    'y' =>floatval($n4),
                    'name' => $nama,
                    'drilldown' => $ctg[1]
                ),
                array(
                    'y' =>floatval($n5),
                    'name' => $nama,
                    'drilldown' => $ctg[2]
                ), 
                array(
                    'y' =>floatval($n6),
                    'name' => $nama,
                    'drilldown' => $ctg[3]
                )
            );

            $_series = [
                'name' => $nama,
                'data' => $data
            ];

            array_push($series, $_series);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'series' => $series,
                'drilldown' => $drill
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            
            $success['data'] = [
                'status' => false,
                'message' => "Error ".$e,
                'kategori' => [],
                'series' => [],
                'drilldown' => []
            ];
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API detail kelompok Yoy
     * 
     */
    public function getDataKelompokAkun(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            if($r->query('kode_grafik')[1] == "PI01") {
                $kode_grafik = "PI05";
            } elseif($r->query('kode_grafik')[1] == "PI02") {
                $kode_grafik = "PI06";
            } else {
                $kode_grafik = $r->query('kode_grafik')[1];
            }
            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $lokasi = $r->kode_lokasi;
            }else{
                $lokasi = $kode_lokasi;
            }
            $where = "WHERE a.kode_lokasi = '$lokasi' AND a.kode_grafik = '".$kode_grafik."' AND a.kode_fs='FS1'";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_neraca, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.capai,0) AS capai
            FROM neraca a
            INNER JOIN (
                SELECT a.kode_lokasi,a.kode_neraca,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi,a.kode_neraca
            ) b ON a.kode_lokasi=b.kode_lokasi AND a.kode_neraca=b.kode_neraca 
            WHERE a.kode_lokasi='$lokasi' AND a.kode_fs='FS1'";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $total = 0;
            foreach($res as $item) {
                $total = $total + floatval(abs($item['n4']));
            }

            $idx = 0;
            $chart = [];
            if($total > 0) { 
                foreach($res as $item) { 
                    $persen = (floatval(abs($item['n4'])) / $total) * 100;
                    $_persen = number_format((float)$persen, 2,'.', '');

                    $data = [
                        'name' => $item['nama'],
                        'y' => floatval($_persen),
                        'z' => intval($item['n4'])
                    ];
                    array_push($chart, $data);
                }
            } else {
                foreach($res as $item) { 
                    $data = [
                        'name' => $item['nama'],
                        'y' => 0,
                        'z' => 0
                    ];
                    array_push($chart, $data);
                }
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $chart;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataOR5TahunBackup(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            // $col_array = array('kode_grafik');
            // $db_col_name = array('a.kode_grafik');
            if($r->query('kode_grafik')[1] == "PI01") {
                $kode_grafik = "PI05";
            } elseif($r->query('kode_grafik')[1] == "PI02") {
                $kode_grafik = "PI06";
            } else {
                $kode_grafik = $r->query('kode_grafik')[1];
            }

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $filter_lokasi = " and a.kode_lokasi='$r->kode_lokasi'";
            }else{
                $filter_lokasi = "";
            }

            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }

            $where = "WHERE a.kode_lokasi IN ('11','12','13','14','15') and a.kode_grafik = '".$kode_grafik."' and a.kode_fs='FS1' $filter_lokasi ";

            $tahun = intval($r->query('periode')[1]);
            $periode = [];
            for($i=0;$i<5;$i++) {
                if($i == 0) {
                    array_push($periode, $tahun);
                } else {
                    $tahun = $tahun - 100;
                    array_push($periode, $tahun);
                }
            }

            $sql = "SELECT DISTINCT a.kode_neraca,UPPER(a.nama) as nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, ISNULL(b.n3,0) AS n3, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5,c.skode as lokasi
            FROM neraca a
            INNER JOIN dash_ypt_lokasi c on a.kode_lokasi=c.kode_lokasi
            INNER JOIN (
                SELECT a.kode_neraca,a.kode_lokasi,
                    SUM(CASE WHEN b.jenis_akun <> 'Pendapatan' THEN ISNULL(b.$n4,0) ELSE -ISNULL(b.$n4,0) END) AS n1,
                    SUM(CASE WHEN c.jenis_akun <> 'Pendapatan' THEN ISNULL(c.$n4,0) ELSE -ISNULL(c.$n4,0) END) AS n2,
                    SUM(CASE WHEN d.jenis_akun <> 'Pendapatan' THEN ISNULL(d.$n4,0) ELSE -ISNULL(d.$n4,0) END) AS n3,
                    SUM(CASE WHEN e.jenis_akun <> 'Pendapatan' THEN ISNULL(e.$n4,0) ELSE -ISNULL(e.$n4,0) END) AS n4,
                    SUM(CASE WHEN f.jenis_akun <> 'Pendapatan' THEN ISNULL(f.$n4,0) ELSE -ISNULL(f.$n4,0) END) AS n5
                    FROM dash_ypt_grafik_d a
                    INNER JOIN dash_ypt_grafik_m x ON a.kode_grafik=x.kode_grafik AND a.kode_lokasi=x.kode_lokasi
                    LEFT JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs AND b.periode='".$periode[4]."'
                    LEFT JOIN exs_neraca c ON a.kode_neraca=c.kode_neraca AND a.kode_lokasi=c.kode_lokasi AND a.kode_fs=c.kode_fs AND c.periode='".$periode[3]."'
                    LEFT JOIN exs_neraca d ON a.kode_neraca=d.kode_neraca AND a.kode_lokasi=d.kode_lokasi AND a.kode_fs=d.kode_fs AND d.periode='".$periode[2]."'
                    LEFT JOIN exs_neraca e ON a.kode_neraca=e.kode_neraca AND a.kode_lokasi=e.kode_lokasi AND a.kode_fs=e.kode_fs AND e.periode='".$periode[1]."'
                    LEFT JOIN exs_neraca f ON a.kode_neraca=f.kode_neraca AND a.kode_lokasi=f.kode_lokasi AND a.kode_fs=f.kode_fs AND f.periode='".$periode[0]."'
                    $where
                GROUP BY a.kode_neraca,a.kode_lokasi
            )b ON a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi IN ('11','12','13','14','15') and a.kode_fs='FS1' $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            // $color = ['#1D4ED8', '#EC4899', '#EC4899'];
            $ctg = [];
            for($i=0;$i<5;$i++) {
                array_unshift($ctg, substr($periode[$i], 0, 4));
            }
            
            $series = [];
            foreach($res as $item) {
                $data = [];
                array_unshift($data, floatval($item['n1']),  floatval($item['n2']),  floatval($item['n3']), floatval($item['n4']), floatval($item['n5']));

                $_series = [
                    'name' => $item['lokasi'],
                    'data' => $data
                ];

                array_push($series, $_series);
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'series' => $series,
                'periode' => $periode
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataOR5Tahun(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            // $col_array = array('kode_grafik');
            // $db_col_name = array('a.kode_grafik');
            if($r->query('kode_grafik')[1] == "PI01") {
                $kode_grafik = "PI05";
            } elseif($r->query('kode_grafik')[1] == "PI02") {
                $kode_grafik = "PI06";
            } else {
                $kode_grafik = $r->query('kode_grafik')[1];
            }

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $filter_lokasi = " and a.kode_lokasi='$r->kode_lokasi'";
            }else{
                $filter_lokasi = "";
            }

            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }

            $where = "WHERE a.kode_lokasi IN ('11','12','13','14','15') and a.kode_grafik = '".$kode_grafik."' and a.kode_fs='FS1' $filter_lokasi ";

            $tahun = intval($r->query('periode')[1]);
            $periode = [];
            for($i=0;$i<5;$i++) {
                if($i == 0) {
                    array_push($periode, $tahun);
                } else {
                    $tahun = $tahun - 100;
                    array_push($periode, $tahun);
                }
            }

            $sql = "SELECT DISTINCT a.kode_neraca,UPPER(a.nama) as nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n2,0) AS n2, ISNULL(b.n3,0) AS n3, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5,c.skode as lokasi
            FROM neraca a
            INNER JOIN dash_ypt_lokasi c on a.kode_lokasi=c.kode_lokasi
            INNER JOIN (
                SELECT a.kode_neraca,a.kode_lokasi,
                    SUM(CASE WHEN b.jenis_akun <> 'Pendapatan' THEN ISNULL(b.$n4,0) ELSE -ISNULL(b.$n4,0) END) AS n1,
                    SUM(CASE WHEN c.jenis_akun <> 'Pendapatan' THEN ISNULL(c.$n4,0) ELSE -ISNULL(c.$n4,0) END) AS n2,
                    SUM(CASE WHEN d.jenis_akun <> 'Pendapatan' THEN ISNULL(d.$n4,0) ELSE -ISNULL(d.$n4,0) END) AS n3,
                    SUM(CASE WHEN e.jenis_akun <> 'Pendapatan' THEN ISNULL(e.$n4,0) ELSE -ISNULL(e.$n4,0) END) AS n4,
                    SUM(CASE WHEN f.jenis_akun <> 'Pendapatan' THEN ISNULL(f.$n4,0) ELSE -ISNULL(f.$n4,0) END) AS n5
                    FROM dash_ypt_grafik_d a
                    INNER JOIN dash_ypt_grafik_m x ON a.kode_grafik=x.kode_grafik AND a.kode_lokasi=x.kode_lokasi
                    LEFT JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs AND b.periode='".$periode[4]."'
                    LEFT JOIN exs_neraca c ON a.kode_neraca=c.kode_neraca AND a.kode_lokasi=c.kode_lokasi AND a.kode_fs=c.kode_fs AND c.periode='".$periode[3]."'
                    LEFT JOIN exs_neraca d ON a.kode_neraca=d.kode_neraca AND a.kode_lokasi=d.kode_lokasi AND a.kode_fs=d.kode_fs AND d.periode='".$periode[2]."'
                    LEFT JOIN exs_neraca e ON a.kode_neraca=e.kode_neraca AND a.kode_lokasi=e.kode_lokasi AND a.kode_fs=e.kode_fs AND e.periode='".$periode[1]."'
                    LEFT JOIN exs_neraca f ON a.kode_neraca=f.kode_neraca AND a.kode_lokasi=f.kode_lokasi AND a.kode_fs=f.kode_fs AND f.periode='".$periode[0]."'
                    $where
                GROUP BY a.kode_neraca,a.kode_lokasi
            )b ON a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi IN ('11','12','13','14','15') and a.kode_fs='FS1' $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            // $color = ['#1D4ED8', '#EC4899', '#EC4899'];
            $ctg = [];
            for($i=0;$i<5;$i++) {
                array_unshift($ctg, substr($periode[$i], 0, 4));
            }
            
            $series = [];
            $drill = [];
            $n1=0;$n2=0;$n3=0;$n4=0;$n5=0; $i=0;
            $color =  ["#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1","#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1","#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1","#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1","#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"];
            foreach($res as $item) {
                $n1+= floatval($item['n1']);
                $n2+= floatval($item['n2']);
                $n3+= floatval($item['n3']);
                $n4+= floatval($item['n4']);
                $n5+= floatval($item['n5']);

                $dd = [];
                array_unshift($dd, floatval($item['n1']), floatval($item['n2']), floatval($item['n3']), floatval($item['n4']), floatval($item['n5']));

                $_drill = [
                    'name' => $item['lokasi'],
                    'color' => $color[$i],
                    'data' => $dd
                ];

                array_push($drill, $_drill);
                $i++;
            }
            $data = [];
            $nama = 'Total';
            array_unshift($data, 
                array(
                    'y' =>floatval($n1),
                    'name' => $nama,
                    'drilldown' => $ctg[0]
                ), 
                array(
                    'y' =>floatval($n2),
                    'name' => $nama,
                    'drilldown' => $ctg[1]
                ),
                array(
                    'y' =>floatval($n3),
                    'name' => $nama,
                    'drilldown' => $ctg[2]
                ), 
                array(
                    'y' =>floatval($n4),
                    'name' => $nama,
                    'drilldown' => $ctg[3]
                ), 
                array(
                    'y' =>floatval($n5),
                    'name' => $nama,
                    'drilldown' => $ctg[4]
                )
            );

            $_series = [
                'name' => $nama,
                'data' => $data
            ];

            array_push($series, $_series);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'series' => $series,
                'periode' => $periode,
                'drilldown' => $drill
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            
            $success['data'] = [
                'status' => false,
                'message' => "Error ".$e,
                'kategori' => [],
                'series' => [],
                'drilldown' => []
            ];
            return response()->json($success, $this->successStatus);
        }
    }

}
