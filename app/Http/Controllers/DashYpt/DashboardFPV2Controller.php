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
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi='20' AND a.kode_fs='FS1' AND a.kode_grafik in ('PI01','PI02','PI03','PI04')";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "SELECT a.kode_grafik, c.nama,
            CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END AS n1,
            CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n2 ELSE b.n2 END AS n2,
            CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n4 ELSE b.n4 END AS n4,
            CASE WHEN b.jenis_akun='Pendapatan' THEN -b.n5 ELSE b.n5 END AS n5,
            CASE WHEN b.n1<>0 THEN (b.n4/b.n1)*100 ELSE 0 END AS capai,
            CASE ISNULL(b.n2,0) WHEN 0 THEN 0 ELSE (b.n4/b.n2)*100 END AS ach,
            CASE ISNULL(b.n4,0) WHEN 0 THEN 0 ELSE ((b.n4 - b.n5)/b.n4)*100 END AS yoy
            FROM dash_ypt_grafik_d a
            INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
            INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
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
                        "n2" => floatval(number_format((float)$item['n2'], 1, '.', '')),
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
                        "n2" => floatval(number_format((float)$item['n2'], 1, '.', '')),
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
                        "n2" => floatval(number_format((float)$item['n2'], 1, '.', '')),
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
                        "n2" => floatval(number_format((float)$item['n2'], 1, '.', '')),
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

    public function getDataOR(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik in ('PI04')";
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
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $anggaran = [];
            $realisasi = [];
            $ctg = [];
            foreach($res as $item) {  

                $name= $item['skode'];

                array_push($ctg, $name);
                array_push($anggaran, floatval(number_format((float)$item['n2'], 1, '.', '')));
                array_push($realisasi, floatval(number_format((float)$item['n4'], 1, '.', '')));
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'kategori' => $ctg,
                'anggaran' => $anggaran,
                'realisasi' => $realisasi
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
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik in ('PI03')";
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
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

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
                'realisasi' => $realisasi
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
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI02')";
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
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

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
                'realisasi' => $realisasi
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
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi in ('03','11','12','13','14','15') AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI01')";
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
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15')";

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
                'realisasi' => $realisasi
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