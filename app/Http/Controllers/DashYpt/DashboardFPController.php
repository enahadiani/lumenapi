<?php

namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardFPController extENDs Controller
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
                    $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                    $tmp = explode(",",$request->input($col_array[$i])[1]);
                    $this_in = "";
                    for($x=0;$x<count($tmp);$x++){
                        if($x == 0){
                            $this_in .= "'".$tmp[$x]."'";
                        }else{
        
                            $this_in .= ","."'".$tmp[$x]."'";
                        }
                    }
                    $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                }else if($request->input($col_array[$i])[0] == "<>" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " and ".$db_col_name[$i]." <> '".$request->input($col_array[$i])[1]."' ";
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
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi='20' AND a.kode_fs='FS1' AND a.kode_grafik in ('PI01','PI02','PI03','PI04')";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_grafik, c.nama,
            CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END AS n1,
            CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END AS n4,
            CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END AS n5,
            CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END AS capai,
            CASE ISNULL(b.n2,0) WHEN 0 THEN 0 ELSE (b.n4/b.n2)*100 END AS ach,
            CASE ISNULL(b.n4,0) WHEN 0 THEN 0 ELSE ((b.n4 - b.n5)/b.n4)*100 END AS yoy
            FROM db_grafik_d a
            INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
            INNER JOIN db_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
            $where";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $data_pdpt = array();
            $data_beban = array();
            $data_shu = array();
            $data_or = array();
            foreach($res as $item) {
                if($item['kode_grafik'] == 'PI01') {
                    $data_pdpt = [
                        "kode_grafik" => $item['kode_grafik'],
                        "nama" => $item['nama'],
                        "n1" => floatval(number_format((float)$item['n1'], 1, '.', '')),
                        "n4" => floatval(number_format((float)$item['n4'], 1, '.', '')),
                        "n5" => floatval(number_format((float)$item['n5'], 1, '.', '')),
                        "capai" => floatval(number_format((float)$item['capai'], 1, '.', '')),
                        "ach" => floatval(number_format((float)$item['ach'], 1, '.', '')),
                        "yoy" => floatval(number_format((float)$item['yoy'], 1, '.', '')),
                    ];
                } elseif($item['kode_grafik'] == 'PI02') {
                    $data_beban = [
                        "kode_grafik" => $item['kode_grafik'],
                        "nama" => $item['nama'],
                        "n1" => floatval(number_format((float)$item['n1'], 1, '.', '')),
                        "n4" => floatval(number_format((float)$item['n4'], 1, '.', '')),
                        "n5" => floatval(number_format((float)$item['n5'], 1, '.', '')),
                        "capai" => floatval(number_format((float)$item['capai'], 1, '.', '')),
                        "ach" => floatval(number_format((float)$item['ach'], 1, '.', '')),
                        "yoy" => floatval(number_format((float)$item['yoy'], 1, '.', '')),
                    ];
                } elseif($item['kode_grafik'] == 'PI03') {
                    $data_shu = [
                        "kode_grafik" => $item['kode_grafik'],
                        "nama" => $item['nama'],
                        "n1" => floatval(number_format((float)$item['n1'], 1, '.', '')),
                        "n4" => floatval(number_format((float)$item['n4'], 1, '.', '')),
                        "n5" => floatval(number_format((float)$item['n5'], 1, '.', '')),
                        "capai" => floatval(number_format((float)$item['capai'], 1, '.', '')),
                        "ach" => floatval(number_format((float)$item['ach'], 1, '.', '')),
                        "yoy" => floatval(number_format((float)$item['yoy'], 1, '.', '')),
                    ];
                } elseif($item['kode_grafik'] == 'PI04') {
                    $data_or = [
                        "kode_grafik" => $item['kode_grafik'],
                        "nama" => $item['nama'],
                        "n1" => floatval(number_format((float)$item['n1'], 1, '.', '')),
                        "n4" => floatval(number_format((float)$item['n4'], 1, '.', '')),
                        "n5" => floatval(number_format((float)$item['n5'], 1, '.', '')),
                        "capai" => floatval(number_format((float)$item['capai'], 1, '.', '')),
                        "ach" => floatval(number_format((float)$item['ach'], 1, '.', '')),
                        "yoy" => floatval(number_format((float)$item['yoy'], 1, '.', '')),
                    ];
                }
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "data_pdpt" => $data_pdpt,
                "data_beban" => $data_beban,
                "data_shu" => $data_shu,
                "data_or" => $data_or
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API data box Pendapatan
     * 
     */
    public function getDataBoxPdpt(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI01')";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.capai,0) as capai
            FROM lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM db_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN db_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $total = 0;
            foreach($res as $item) {
                $total = $total + floatval(abs($item['n4']));
            }

            $chart = [];
            if($total > 0) {
                foreach($res as $item) { 
                    $persen = (floatval(abs($item['n4'])) / $total) * 100;
                    $_persen = number_format((float)$persen, 1, '.', '');
                    
                    if($item['kode_lokasi'] == '03') {
                        $name = "KAPEL";
                    } elseif($item['kode_lokasi'] == '11') {
                        $name = "TelU";
                    } elseif($item['kode_lokasi'] == '12') {
                        $name = "TS";
                    } elseif($item['kode_lokasi'] == '13') {
                        $name = "ITTJ";
                    } elseif($item['kode_lokasi'] == '14') {
                        $name = "ITTP";
                    } elseif($item['kode_lokasi'] == '15') {
                        $name = "ITTS";
                    }

                    $value = [
                        'name' => $name,
                        'y' => floatval($_persen)
                    ];
                    array_push($chart, $value);
                }
            } else {
                foreach($res as $item) {
                    $_persen = 0;
                    
                    if($item['kode_lokasi'] == '03') {
                        $name = "KAPEL";
                    } elseif($item['kode_lokasi'] == '11') {
                        $name = "TelU";
                    } elseif($item['kode_lokasi'] == '12') {
                        $name = "TS";
                    } elseif($item['kode_lokasi'] == '13') {
                        $name = "ITTJ";
                    } elseif($item['kode_lokasi'] == '14') {
                        $name = "ITTP";
                    } elseif($item['kode_lokasi'] == '15') {
                        $name = "ITTS";
                    }

                    $value = [
                        'name' => $name,
                        'y' => floatval($_persen)
                    ];
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
     * Function ini untuk API data box beban
     */
    public function getDataBoxBeban(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI02')";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.capai,0) as capai
            FROM lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM db_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN db_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $total = 0;
            foreach($res as $item) {
                $total = $total + floatval(abs($item['n4']));
            }

            $chart = [];
            if($total > 0) {
                foreach($res as $item) { 
                    $persen = (floatval(abs($item['n4'])) / $total) * 100;
                    $_persen = number_format((float)$persen, 1, '.', '');
                    
                    if($item['kode_lokasi'] == '03') {
                        $name = "KAPEL";
                    } elseif($item['kode_lokasi'] == '11') {
                        $name = "TelU";
                    } elseif($item['kode_lokasi'] == '12') {
                        $name = "TS";
                    } elseif($item['kode_lokasi'] == '13') {
                        $name = "ITTJ";
                    } elseif($item['kode_lokasi'] == '14') {
                        $name = "ITTP";
                    } elseif($item['kode_lokasi'] == '15') {
                        $name = "ITTS";
                    }

                    $value = [
                        'name' => $name,
                        'y' => floatval($_persen)
                    ];
                    array_push($chart, $value);
                }
            } else {
                foreach($res as $item) {
                    $_persen = 0;
                    
                    if($item['kode_lokasi'] == '03') {
                        $name = "KAPEL";
                    } elseif($item['kode_lokasi'] == '11') {
                        $name = "TelU";
                    } elseif($item['kode_lokasi'] == '12') {
                        $name = "TS";
                    } elseif($item['kode_lokasi'] == '13') {
                        $name = "ITTJ";
                    } elseif($item['kode_lokasi'] == '14') {
                        $name = "ITTP";
                    } elseif($item['kode_lokasi'] == '15') {
                        $name = "ITTS";
                    }

                    $value = [
                        'name' => $name,
                        'y' => floatval($_persen)
                    ];
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
     * Function ini untuk API data box SHU
     */
    public function getDataBoxShu(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') and a.kode_fs='FS1' and a.kode_grafik in ('PI03')";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5,
            ISNULL(b.capai,0) AS capai
            FROM lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM db_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN db_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $total = 0;
            foreach($res as $item) {
                $total = $total + floatval(abs($item['n4']));
            }

            $chart = [];
            if($total > 0) {
                foreach($res as $item) { 
                    $persen = (floatval(abs($item['n4'])) / $total) * 100;
                    $_persen = number_format((float)$persen, 1, '.', '');
                    
                    if($item['kode_lokasi'] == '03') {
                        $name = "KAPEL";
                    } elseif($item['kode_lokasi'] == '11') {
                        $name = "TelU";
                    } elseif($item['kode_lokasi'] == '12') {
                        $name = "TS";
                    } elseif($item['kode_lokasi'] == '13') {
                        $name = "ITTJ";
                    } elseif($item['kode_lokasi'] == '14') {
                        $name = "ITTP";
                    } elseif($item['kode_lokasi'] == '15') {
                        $name = "ITTS";
                    }

                    $value = [
                        'name' => $name,
                        'y' => floatval($_persen)
                    ];
                    array_push($chart, $value);
                }
            } else {
                foreach($res as $item) {
                    $_persen = 0;
                    
                    if($item['kode_lokasi'] == '03') {
                        $name = "KAPEL";
                    } elseif($item['kode_lokasi'] == '11') {
                        $name = "TelU";
                    } elseif($item['kode_lokasi'] == '12') {
                        $name = "TS";
                    } elseif($item['kode_lokasi'] == '13') {
                        $name = "ITTJ";
                    } elseif($item['kode_lokasi'] == '14') {
                        $name = "ITTP";
                    } elseif($item['kode_lokasi'] == '15') {
                        $name = "ITTS";
                    }

                    $value = [
                        'name' => $name,
                        'y' => floatval($_persen)
                    ];
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
     * Function ini untuk API data box OR
     */
    public function getDataBoxOr(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') and a.kode_fs='FS1' and a.kode_grafik in ('PI04')";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi,a.nama, ISNULL(b.n1,0) as n1, ISNULL(b.n4,0) as n4, ISNULL(b.n5,0) as n5, 
            ISNULL(b.capai,0) as capai
            FROM lokasi a
            LEFT JOIN (SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM db_grafik_d a
                INNER JOIN exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                INNER JOIN db_grafik_m c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $total = 0;
            foreach($res as $item) {
                $total = $total + floatval(abs($item['n4']));
            }

            $chart = [];
            if($total > 0) {
                foreach($res as $item) { 
                    $persen = (floatval(abs($item['n4'])) / $total) * 100;
                    $_persen = number_format((float)$persen, 1, '.', '');
                    
                    if($item['kode_lokasi'] == '03') {
                        $name = "KAPEL";
                    } elseif($item['kode_lokasi'] == '11') {
                        $name = "TelU";
                    } elseif($item['kode_lokasi'] == '12') {
                        $name = "TS";
                    } elseif($item['kode_lokasi'] == '13') {
                        $name = "ITTJ";
                    } elseif($item['kode_lokasi'] == '14') {
                        $name = "ITTP";
                    } elseif($item['kode_lokasi'] == '15') {
                        $name = "ITTS";
                    }

                    $value = [
                        'name' => $name,
                        'y' => floatval($_persen)
                    ];
                    array_push($chart, $value);
                }
            } else {
                foreach($res as $item) {
                    $_persen = 0;
                    
                    if($item['kode_lokasi'] == '03') {
                        $name = "KAPEL";
                    } elseif($item['kode_lokasi'] == '11') {
                        $name = "TelU";
                    } elseif($item['kode_lokasi'] == '12') {
                        $name = "TS";
                    } elseif($item['kode_lokasi'] == '13') {
                        $name = "ITTJ";
                    } elseif($item['kode_lokasi'] == '14') {
                        $name = "ITTP";
                    } elseif($item['kode_lokasi'] == '15') {
                        $name = "ITTS";
                    }

                    $value = [
                        'name' => $name,
                        'y' => floatval($_persen)
                    ];
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
     * Function ini untuk API data box laba rugi
     */
    public function getDataBoxLabaRugi(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') and a.kode_fs='FS1'";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.pdpt,0) AS pdpt, ISNULL(b.beban,0) AS beban, 
            ISNULL(b.shu,0) AS shu
            FROM lokasi a
            LEFT JOIN (SELECT a.kode_lokasi,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) ELSE 0 END) AS pdpt,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) ELSE 0 END) AS beban,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) ELSE 0 END) AS shu		
                FROM db_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN db_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

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

                if($item['kode_lokasi'] == '03') {
                    $name = "KAPEL";
                } elseif($item['kode_lokasi'] == '11') {
                    $name = "TelU";
                } elseif($item['kode_lokasi'] == '12') {
                    $name = "TS";
                } elseif($item['kode_lokasi'] == '13') {
                    $name = "ITTJ";
                } elseif($item['kode_lokasi'] == '14') {
                    $name = "ITTP";
                } elseif($item['kode_lokasi'] == '15') {
                    $name = "ITTS";
                }

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
                'data_shu' => $data_shu
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
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') and a.kode_fs='FS1'";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.pdpt_ach,0) AS pdpt_ach, ISNULL(b.pdpt_yoy,0) as pdpt_yoy,
                ISNULL(b.beban_ach,0) as beban_ach,ISNULL(b.beban_yoy,0) as beban_yoy,
                ISNULL(b.shu_ach,0) as shu_ach,ISNULL(b.shu_yoy,0) as shu_yoy,
                ISNULL(b.or_ach,0) as or_ach,ISNULL(b.or_yoy,0) as or_yoy
            from lokasi a
            LEFT JOIN (SELECT a.kode_lokasi,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (CASE ISNULL(b.n2,0) WHEN 0 THEN 0 ELSE (b.n4/b.n2)*100 END) ELSE 0 END) AS pdpt_ach,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (CASE ISNULL(b.n4,0) WHEN 0 THEN 0 ELSE ((b.n4 - b.n5)/b.n4)*100 END) ELSE 0 END) AS pdpt_yoy,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (CASE ISNULL(b.n2,0) WHEN 0 THEN 0 ELSE (b.n4/b.n2)*100 END) ELSE 0 END) AS beban_ach,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (CASE ISNULL(b.n4,0) WHEN 0 THEN 0 ELSE ((b.n4 - b.n5)/b.n4)*100 END) ELSE 0 END) AS beban_yoy,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (CASE ISNULL(b.n2,0) WHEN 0 THEN 0 ELSE (b.n4/b.n2)*100 END) ELSE 0 END) AS shu_ach,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (CASE ISNULL(b.n4,0) WHEN 0 THEN 0 ELSE ((b.n4 - b.n5)/b.n4)*100 END) ELSE 0 END) AS shu_yoy,
                SUM(CASE WHEN a.kode_grafik='PI04' THEN (CASE ISNULL(b.n2,0) WHEN 0 THEN 0 ELSE (b.n4/b.n2)*100 END) ELSE 0 END) AS or_ach,
                SUM(CASE WHEN a.kode_grafik='PI04' THEN (CASE ISNULL(b.n4,0) WHEN 0 THEN 0 ELSE ((b.n4 - b.n5)/b.n4)*100 END) ELSE 0 END) AS or_yoy
                FROM db_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN db_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b on a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $data_perform = [];
            foreach($res as $item) { 
                if($item['kode_lokasi'] == '03') {
                    $name = "KAPEL";
                } elseif($item['kode_lokasi'] == '11') {
                    $name = "TelU";
                } elseif($item['kode_lokasi'] == '12') {
                    $name = "TS";
                } elseif($item['kode_lokasi'] == '13') {
                    $name = "ITTJ";
                } elseif($item['kode_lokasi'] == '14') {
                    $name = "ITTP";
                } elseif($item['kode_lokasi'] == '15') {
                    $name = "ITTS";
                }

                $perform = [
                    "kode_lokasi" => $item['kode_lokasi'],
                    "nama" => $name,
                    "pdpt_ach" => floatval(number_format((float)$item['pdpt_ach'], 1, '.', '')),
                    "pdpt_yoy" => floatval(number_format((float)$item['pdpt_yoy'], 1, '.', '')),
                    "beban_ach" => floatval(number_format((float)$item['beban_ach'], 1, '.', '')),
                    "beban_yoy" => floatval(number_format((float)$item['beban_yoy'], 1, '.', '')),
                    "shu_ach" => floatval(number_format((float)$item['shu_ach'], 1, '.', '')),
                    "shu_yoy" => floatval(number_format((float)$item['shu_yoy'], 1, '.', '')),
                    "or_ach" => floatval(number_format((float)$item['or_ach'], 1, '.', '')),
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
            
            $col_array = array('periode', 'kode_grafik');
            $db_col_name = array('b.periode', 'a.kode_grafik');
            $where = "WHERE a.kode_fs = 'FS1'";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_lokasi, a.nama, ISNULL(b.n1,0) AS n1, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
            ISNULL(b.capai,0) as capai
            FROM lokasi a
            LEFT JOIN (
                SELECT a.kode_lokasi,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n1,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END) AS n5,
                SUM(CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END) AS capai
                FROM db_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN db_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where
                GROUP BY a.kode_lokasi
            ) b ON a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $ctg = [];
            $data_realisasi = [];
            $data_anggaran = [];
            foreach($res as $item) {
                if($item['kode_lokasi'] == '03') {
                    $name = "KAPEL";
                } elseif($item['kode_lokasi'] == '11') {
                    $name = "TelU";
                } elseif($item['kode_lokasi'] == '12') {
                    $name = "TS";
                } elseif($item['kode_lokasi'] == '13') {
                    $name = "ITTJ";
                } elseif($item['kode_lokasi'] == '14') {
                    $name = "ITTP";
                } elseif($item['kode_lokasi'] == '15') {
                    $name = "ITTS";
                }

                $realisasi = floatval(number_format((float)$item['capai'], 1, '.', ''));
                $sisa = 100 - $realisasi;
                $anggaran = floatval(number_format((float)$sisa, 1, '.', ''));
                
                array_push($ctg, $name);
                array_push($data_realisasi, $realisasi);
                array_push($data_anggaran, $anggaran);
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "ketegori" => $ctg,
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

}
