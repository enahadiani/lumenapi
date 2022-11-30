<?php

namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardFPTsController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $sql = 'sqlsrvyptkug';

    private function filterReq($request, $col_array, $db_col_name, $where, $this_in)
    {
        for ($i = 0; $i < count($col_array); $i++) {
            if (isset($request->input($col_array[$i])[0])) {
                if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                    $where .= " AND (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                } elseif ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                    $where .= " AND " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                } elseif ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                    $tmp = explode(",", $request->input($col_array[$i])[1]);
                    $this_in = "";
                    for ($x = 0; $x < count($tmp); $x++) {
                        if ($x == 0) {
                            $this_in .= "'" . $tmp[$x] . "'";
                        } else {

                            $this_in .= "," . "'" . $tmp[$x] . "'";
                        }
                    }
                    $where .= " AND " . $db_col_name[$i] . " in ($this_in) ";
                } elseif ($request->input($col_array[$i])[0] == "<=" and isset($request->input($col_array[$i])[1])) {
                    $where .= " AND " . $db_col_name[$i] . " <= '" . $request->input($col_array[$i])[1] . "' ";
                } elseif ($request->input($col_array[$i])[0] == "<>" and isset($request->input($col_array[$i])[1])) {
                    $where .= " AND " . $db_col_name[$i] . " <> '" . $request->input($col_array[$i])[1] . "' ";
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
    public function getDataBoxFirst(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $kode_lokasi = $r->kode_lokasi;
            if (isset($r->jenis) && $r->jenis != "") {
                if ($r->jenis == "PRD") {
                    $n4 = "n6";
                    $n2 = "n7";
                    $n5 = "n9";
                } else {
                    $n4 = "n4";
                    $n2 = "n2";
                    $n5 = "n5";
                }
            } else {
                $n4 = "n4";
                $n2 = "n2";
                $n5 = "n5";
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi='$kode_lokasi' AND a.kode_fs='FS1' AND a.kode_grafik in ('PI01','PI02','PI03','PI04')";
            $where = $this->filterReq($r, $col_array, $db_col_name, $where, "");

            if (isset($r->kode_bidang) && $r->kode_bidang != "") {
                $filter_bidang = " and p.kode_bidang='$r->kode_bidang' ";
                $filter_pp = "";
                if(isset($r->kode_pp) && $r->kode_pp != ""){
                    $filter_pp = " and p.kode_pp='$r->kode_pp' ";
                }
                $sql = "SELECT a.kode_grafik, c.nama,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                INNER JOIN pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
                $where $filter_bidang $filter_pp
                group by a.kode_grafik, c.nama
                order by a.kode_grafik ";
            } else {

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
            }


            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select), true);

            $data = array();
            foreach ($res as $item) {
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

            $orN2 = ($data[1]['n2'] / $data[0]['n2']) * 100;
            $orN4 = ($data[1]['n4'] / $data[0]['n4']) * 100;
            $orN5 = ($data[1]['n5'] / $data[0]['n5']) * 100;
            $orAch = ($data[1]['n2'] / $data[0]['n4']) * 100;
            $orYoy = (($data[1]['n4'] - $data[1]['n5']) / $data[0]['n4']) * 100;
            $data_or = [
                "kode_grafik" => "PI04",
                "nama" => "OR",
                "n2" => floatval(number_format((float)$orN2, 2, '.', '')),
                "n4" => floatval(number_format((float)$orN4, 2, '.', '')),
                "n5" => floatval(number_format((float)$orN5, 2, '.', '')),
                "ach" => floatval(number_format((float)$orAch, 2, '.', '')),
                "yoy" => floatval(number_format((float)$orYoy, 2, '.', '')),
            ];

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "data_pdpt" => $data[0],
                "data_beban" => $data[1],
                "data_shu" => $data[2],
                "data_or" => $data_or
            ];

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            $success['data'] = [
                "data_pdpt" => [],
                "data_beban" => [],
                "data_shu" => [],
                "data_or" => [],
                "message" => "Error " . $e
            ];
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API data box laba rugi
     */
    public function getDataBoxLabaRugi(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            $kode_lokasi = $r->kode_lokasi;
            if (isset($r->kode_bidang) && $r->kode_bidang != "") {
                $filter_bidang = " and a.kode_bidang = '$r->kode_bidang' ";
            } else {
                $filter_bidang = "";
            }

            if (isset($r->kode_pp) && $r->kode_pp != "") {
                $filter_pp = " and b.kode_pp = '$r->kode_pp' ";
            } else {
                $filter_pp = "";
            }
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi = '$kode_lokasi' AND a.kode_fs='FS1' ";
            $where = $this->filterReq($r, $col_array, $db_col_name, $where, "");

            if (isset($r->jenis) && $r->jenis != "") {
                if ($r->jenis == "PRD") {
                    $n4 = "n6";
                } else {
                    $n4 = "n4";
                }
            } else {
                $n4 = "n4";
            }

            $sql = "SELECT a.kode_bidang, a.nama, ISNULL(b.pdpt,0) AS pdpt, ISNULL(b.beban,0) AS beban, 
            ISNULL(b.shu,0) AS shu,a.skode
            FROM dash_ypt_bidang a
            LEFT JOIN (SELECT p.kode_bidang,a.kode_lokasi,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS pdpt,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS beban,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) ELSE 0 END) AS shu		
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                $where $filter_pp
                GROUP BY a.kode_lokasi,p.kode_bidang
            ) b ON a.kode_lokasi=b.kode_lokasi and a.kode_bidang=b.kode_bidang
            WHERE a.kode_lokasi ='$kode_lokasi' $filter_bidang";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select), true);

            $ctg = [];
            $data_pdpt = [];
            $data_beban = [];
            $data_shu = [];
            foreach ($res as $item) {
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
                'data_shu' => $data_shu
            ];

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [
                'kategori' => [],
                'data_pdpt' => [],
                'data_beban' => [],
                'data_shu' => [],
                'message' => 'Error ' . $e
            ];
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API data box performansi lembaga
     */
    public function getDataBoxPerformLembaga(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            
            $kode_lokasi = $r->kode_lokasi;
            if (isset($r->kode_bidang) && $r->kode_bidang != "") {
                $filter_bidang = " and a.kode_bidang = '$r->kode_bidang' ";
            } else {
                $filter_bidang = "";
            }

            if (isset($r->kode_pp) && $r->kode_pp != "") {
                $filter_pp = " and b.kode_pp = '$r->kode_pp' ";
            } else {
                $filter_pp = "";
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi ='$kode_lokasi' AND a.kode_fs='FS1' $filter_bidang";
            $where = $this->filterReq($r, $col_array, $db_col_name, $where, "");

            if (isset($r->jenis) && $r->jenis != "") {
                if ($r->jenis == "PRD") {
                    $n4 = "n6";
                    $n2 = "n7";
                    $n5 = "n9";
                } else {
                    $n4 = "n4";
                    $n2 = "n2";
                    $n5 = "n5";
                }
            } else {
                $n4 = "n4";
                $n2 = "n2";
                $n5 = "n5";
            }

            $sql = "SELECT a.kode_bidang, a.nama, a.skode,
            ISNULL(b.pdpt_n2,0) as pdpt_n2, ISNULL(b.pdpt_n4,0) as pdpt_n4, ISNULL(b.pdpt_n5,0) as pdpt_n5,
            ISNULL(b.beban_n2,0) as beban_n2, ISNULL(b.beban_n4,0) as beban_n4, ISNULL(b.beban_n5,0) as beban_n5,
            ISNULL(b.shu_n2,0) as shu_n2, ISNULL(b.shu_n4,0) as shu_n4, ISNULL(b.shu_n5,0) as shu_n5,
            ISNULL(b.or_n2,0) as or_n2, ISNULL(b.or_n4,0) as or_n4, ISNULL(b.or_n5,0) as or_n5
            from dash_ypt_bidang a 
                LEFT JOIN (SELECT a.kode_lokasi,p.kode_bidang,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (case when b.jenis_akun='Pendapatan' then -b.$n4 else b.$n4 end) ELSE 0 END) AS pdpt_n4,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (case when b.jenis_akun='Pendapatan' then -b.$n2 else b.$n2 end) ELSE 0 END) AS pdpt_n2,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (case when b.jenis_akun='Pendapatan' then -b.$n5 else b.$n5 end) ELSE 0 END) AS pdpt_n5,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (case when b.jenis_akun='Pendapatan' then -b.$n4 else b.$n4 end) ELSE 0 END) AS beban_n4,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (case when b.jenis_akun='Pendapatan' then -b.$n2 else b.$n2 end) ELSE 0 END) AS beban_n2,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (case when b.jenis_akun='Pendapatan' then -b.$n5 else b.$n5 end) ELSE 0 END) AS beban_n5,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (case when b.jenis_akun='Pendapatan' then -b.$n4 else b.$n4 end) ELSE 0 END) AS shu_n4,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (case when b.jenis_akun='Pendapatan' then -b.$n2 else b.$n2 end) ELSE 0 END) AS shu_n2,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (case when b.jenis_akun='Pendapatan' then -b.$n5 else b.$n5 end) ELSE 0 END) AS shu_n5,
                SUM(CASE WHEN a.kode_grafik='PI04' THEN (case when b.jenis_akun='Pendapatan' then -b.$n4 else b.$n4 end) ELSE 0 END) AS or_n4,
                SUM(CASE WHEN a.kode_grafik='PI04' THEN (case when b.jenis_akun='Pendapatan' then -b.$n2 else b.$n2 end) ELSE 0 END) AS or_n2,
                SUM(CASE WHEN a.kode_grafik='PI04' THEN (case when b.jenis_akun='Pendapatan' then -b.$n5 else b.$n5 end) ELSE 0 END) AS or_n5
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                INNER JOIN pp p ON b.kode_pp=p.kode_pp AND b.kode_lokasi=p.kode_lokasi
                $where $filter_pp
                GROUP BY a.kode_lokasi,p.kode_bidang
            ) b on a.kode_lokasi=b.kode_lokasi and a.kode_bidang=b.kode_bidang
            WHERE a.kode_lokasi='$kode_lokasi' $filter_bidang";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select), true);

            $data_perform = [];
            foreach ($res as $item) {
                $pdpt_yoy = ($item['pdpt_n5'] != 0 ? (($item['pdpt_n4']-$item['pdpt_n5'])/abs($item['pdpt_n5']))*100 : 0);
                $beban_yoy = ($item['beban_n5'] != 0 ? (($item['beban_n4']-$item['beban_n5'])/abs($item['beban_n5']))*100 : 0);
                $shu_yoy = ($item['shu_n5'] != 0 ? (($item['shu_n4']-$item['shu_n5'])/abs($item['shu_n5']))*100 : 0);
                $or_yoy = ($item['or_n5'] != 0 ? (($item['or_n4']-$item['or_n5'])/abs($item['or_n5']))*100 : 0);
                try{
                    $pdpt_ach = ($item['pdpt_n2'] < 0 ? (1+($item['pdpt_n2']-$item['pdpt_n4'])/$item['pdpt_n2'])*100 : (1+($item['pdpt_n4']-$item['pdpt_n2'])/$item['pdpt_n2'])*100 );
                }catch(\Throwable $e){
                    $pdpt_ach = 0;
                }
                try{
                    $beban_ach = ($item['beban_n2'] < 0 ? (1+($item['beban_n2']-$item['beban_n4'])/$item['beban_n2'])*100 : (1+($item['beban_n4']-$item['beban_n2'])/$item['beban_n2'])*100 );
                }catch(\Throwable $e){
                    $beban_ach = 0;
                }
                try{
                    $shu_ach = ($item['shu_n2'] < 0 ? (1+($item['shu_n2']-$item['shu_n4'])/$item['shu_n2'])*100 : (1+($item['shu_n4']-$item['shu_n2'])/$item['shu_n2'])*100 );
                }catch(\Throwable $e){
                    $shu_ach = 0;
                }
                try{
                    $or_ach = ($item['or_n2']/$item['or_n4'])*100;
                }catch(\Throwable $e){
                    $or_ach = 0;
                }

                if ((float) $pdpt_ach == 0) {
                    $orAch = 0;
                } else {
                    $orAch = ((float) $beban_ach / (float) $pdpt_ach) * 100;
                }

                if ((float) $pdpt_yoy == 0) {
                    $orYoy = 0;
                } else {
                    $orYoy = ((float) $beban_yoy / (float) $pdpt_yoy) * 100;
                }

                $name = $item['skode'];
                $perform = [
                    "kode_bidang" => $item['kode_bidang'],
                    "nama" => $name,
                    "pdpt_ach" => floatval(number_format((float)$pdpt_ach, 2, '.', '')),
                    "pdpt_yoy" => floatval(number_format((float)$pdpt_yoy, 2, '.', '')),
                    "beban_ach" => floatval(number_format((float)$beban_ach, 2, '.', '')),
                    "beban_yoy" => floatval(number_format((float)$beban_yoy, 2, '.', '')),
                    "shu_ach" => floatval(number_format((float)$shu_ach, 2, '.', '')),
                    "shu_yoy" => floatval(number_format((float)$shu_yoy, 2, '.', '')),
                    "or_ach" => floatval(number_format((float)$orAch, 2, '.', '')),
                    "or_yoy" => floatval(number_format((float)$orYoy, 0, '.', '')),
                ];
                array_push($data_perform, $perform);
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $data_perform;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBoxPerformLembagaPP(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            
            $kode_lokasi = $r->kode_lokasi;
            if (isset($r->kode_bidang) && $r->kode_bidang != "") {
                $filter_bidang = " and a.kode_bidang = '$r->kode_bidang' ";
                $filter_bidang2 = " and p.kode_bidang = '$r->kode_bidang' ";
            } else {
                $filter_bidang = "";
                $filter_bidang2 = "";
            }
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "WHERE a.kode_lokasi ='$kode_lokasi' AND a.kode_fs='FS1'";
            $where = $this->filterReq($r, $col_array, $db_col_name, $where, "");

            if (isset($r->jenis) && $r->jenis != "") {
                if ($r->jenis == "PRD") {
                    $n4 = "n6";
                    $n2 = "n7";
                    $n5 = "n9";
                } else {
                    $n4 = "n4";
                    $n2 = "n2";
                    $n5 = "n5";
                }
            } else {
                $n4 = "n4";
                $n2 = "n2";
                $n5 = "n5";
            }

            $sql = "SELECT a.kode_pp, a.nama, a.nama as skode,
            ISNULL(b.pdpt_n2,0) as pdpt_n2, ISNULL(b.pdpt_n4,0) as pdpt_n4, ISNULL(b.pdpt_n5,0) as pdpt_n5,
            ISNULL(b.beban_n2,0) as beban_n2, ISNULL(b.beban_n4,0) as beban_n4, ISNULL(b.beban_n5,0) as beban_n5,
            ISNULL(b.shu_n2,0) as shu_n2, ISNULL(b.shu_n4,0) as shu_n4, ISNULL(b.shu_n5,0) as shu_n5,
            ISNULL(b.or_n2,0) as or_n2, ISNULL(b.or_n4,0) as or_n4, ISNULL(b.or_n5,0) as or_n5
            from pp a 
                LEFT JOIN (SELECT a.kode_lokasi,p.kode_pp,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (case when b.jenis_akun='Pendapatan' then -b.$n4 else b.$n4 end) ELSE 0 END) AS pdpt_n4,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (case when b.jenis_akun='Pendapatan' then -b.$n2 else b.$n2 end) ELSE 0 END) AS pdpt_n2,
                SUM(CASE WHEN a.kode_grafik='PI01' THEN (case when b.jenis_akun='Pendapatan' then -b.$n5 else b.$n5 end) ELSE 0 END) AS pdpt_n5,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (case when b.jenis_akun='Pendapatan' then -b.$n4 else b.$n4 end) ELSE 0 END) AS beban_n4,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (case when b.jenis_akun='Pendapatan' then -b.$n2 else b.$n2 end) ELSE 0 END) AS beban_n2,
                SUM(CASE WHEN a.kode_grafik='PI02' THEN (case when b.jenis_akun='Pendapatan' then -b.$n5 else b.$n5 end) ELSE 0 END) AS beban_n5,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (case when b.jenis_akun='Pendapatan' then -b.$n4 else b.$n4 end) ELSE 0 END) AS shu_n4,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (case when b.jenis_akun='Pendapatan' then -b.$n2 else b.$n2 end) ELSE 0 END) AS shu_n2,
                SUM(CASE WHEN a.kode_grafik='PI03' THEN (case when b.jenis_akun='Pendapatan' then -b.$n5 else b.$n5 end) ELSE 0 END) AS shu_n5,
                SUM(CASE WHEN a.kode_grafik='PI04' THEN (case when b.jenis_akun='Pendapatan' then -b.$n4 else b.$n4 end) ELSE 0 END) AS or_n4,
                SUM(CASE WHEN a.kode_grafik='PI04' THEN (case when b.jenis_akun='Pendapatan' then -b.$n2 else b.$n2 end) ELSE 0 END) AS or_n2,
                SUM(CASE WHEN a.kode_grafik='PI04' THEN (case when b.jenis_akun='Pendapatan' then -b.$n5 else b.$n5 end) ELSE 0 END) AS or_n5
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                INNER JOIN pp p ON b.kode_pp=p.kode_pp AND b.kode_lokasi=p.kode_lokasi
                $where $filter_bidang2
                GROUP BY a.kode_lokasi,p.kode_pp
            ) b on a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            WHERE a.kode_lokasi='$kode_lokasi' $filter_bidang";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select), true);

            $data_perform = [];
            foreach ($res as $item) {
                $pdpt_yoy = ($item['pdpt_n5'] != 0 ? (($item['pdpt_n4']-$item['pdpt_n5'])/abs($item['pdpt_n5']))*100 : 0);
                $beban_yoy = ($item['beban_n5'] != 0 ? (($item['beban_n4']-$item['beban_n5'])/abs($item['beban_n5']))*100 : 0);
                $shu_yoy = ($item['shu_n5'] != 0 ? (($item['shu_n4']-$item['shu_n5'])/abs($item['shu_n5']))*100 : 0);
                $or_yoy = ($item['or_n5'] != 0 ? (($item['or_n4']-$item['or_n5'])/abs($item['or_n5']))*100 : 0);
                try{
                    $pdpt_ach = ($item['pdpt_n2'] < 0 ? (1+($item['pdpt_n2']-$item['pdpt_n4'])/$item['pdpt_n2'])*100 : (1+($item['pdpt_n4']-$item['pdpt_n2'])/$item['pdpt_n2'])*100 );
                }catch(\Throwable $e){
                    $pdpt_ach = 0;
                }
                try{
                    $beban_ach = ($item['beban_n2'] < 0 ? (1+($item['beban_n2']-$item['beban_n4'])/$item['beban_n2'])*100 : (1+($item['beban_n4']-$item['beban_n2'])/$item['beban_n2'])*100 );
                }catch(\Throwable $e){
                    $beban_ach = 0;
                }
                try{
                    $shu_ach = ($item['shu_n2'] < 0 ? (1+($item['shu_n2']-$item['shu_n4'])/$item['shu_n2'])*100 : (1+($item['shu_n4']-$item['shu_n2'])/$item['shu_n2'])*100 );
                }catch(\Throwable $e){
                    $shu_ach = 0;
                }
                try{
                    $or_ach = ($item['or_n2']/$item['or_n4'])*100;
                }catch(\Throwable $e){
                    $or_ach = 0;
                }

                if ((float) $pdpt_ach == 0) {
                    $orAch = 0;
                } else {
                    $orAch = ((float) $beban_ach / (float) $pdpt_ach) * 100;
                }

                if ((float) $pdpt_yoy == 0) {
                    $orYoy = 0;
                } else {
                    $orYoy = ((float) $beban_yoy / (float) $pdpt_yoy) * 100;
                }

                $name = $item['skode'];
                $perform = [
                    "kode_pp" => $item['kode_pp'],
                    "nama" => $name,
                    "pdpt_ach" => floatval(number_format((float)$pdpt_ach, 2, '.', '')),
                    "pdpt_yoy" => floatval(number_format((float)$pdpt_yoy, 2, '.', '')),
                    "beban_ach" => floatval(number_format((float)$beban_ach, 2, '.', '')),
                    "beban_yoy" => floatval(number_format((float)$beban_yoy, 2, '.', '')),
                    "shu_ach" => floatval(number_format((float)$shu_ach, 2, '.', '')),
                    "shu_yoy" => floatval(number_format((float)$shu_yoy, 2, '.', '')),
                    "or_ach" => floatval(number_format((float)$orAch, 2, '.', '')),
                    "or_yoy" => floatval(number_format((float)$orYoy, 0, '.', '')),
                ];
                array_push($data_perform, $perform);
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $data_perform;
            $success['sql'] = $sql;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error $e";
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API detail performansi lembaga
     * 
     */
    public function getDataPerformansiLembaga(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $kode_lokasi = $r->kode_lokasi;
            if (isset($r->kode_bidang) && $r->kode_bidang != "") {
                $filter_bidang = " and a.kode_bidang = '$r->kode_bidang' ";
            } else {
                $filter_bidang = "";
            }
            if (isset($r->kode_pp) && $r->kode_pp != "") {
                $filter_pp = " and b.kode_pp = '$r->kode_pp' ";
            } else {
                $filter_pp = "";
            }

            $col_array = array('periode', 'kode_grafik');
            $db_col_name = array('b.periode', 'a.kode_grafik');
            $where = "WHERE a.kode_fs = 'FS1'  ";
            $where = $this->filterReq($r, $col_array, $db_col_name, $where, "");

            if (isset($r->jenis) && $r->jenis != "") {
                if ($r->jenis == "PRD") {
                    $n4 = "n6";
                    $n2 = "n7";
                    $n5 = "n9";
                } else {
                    $n4 = "n4";
                    $n2 = "n2";
                    $n5 = "n5";
                }
            } else {
                $n4 = "n4";
                $n2 = "n2";
                $n5 = "n5";
            }

            if (isset($r->kode_grafik) && $r->kode_grafik[1] == "PI04") {

                $sql = "SELECT a.kode_bidang, a.nama, ISNULL(b.n2,0) AS n2, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
                ISNULL(b.capai,0) as capai,a.skode
                FROM dash_ypt_bidang a
                LEFT JOIN (
                    SELECT a.kode_lokasi,p.kode_bidang,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5,
                    CASE WHEN sum(b.$n4)<>0 THEN (sum(b.$n2)/sum(b.$n4))*100 ELSE 0 END AS capai
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    INNER JOIN pp p ON b.kode_pp=p.kode_pp AND b.kode_lokasi=p.kode_lokasi
                    $where $filter_pp
                    GROUP BY a.kode_lokasi,p.kode_bidang
                ) b ON a.kode_lokasi=b.kode_lokasi and a.kode_bidang=b.kode_bidang
                WHERE a.kode_lokasi ='$kode_lokasi' $filter_bidang ";
            } else {
                $sql = "SELECT a.kode_bidang, a.nama, ISNULL(b.n2,0) AS n2, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
                ISNULL(b.capai,0) as capai,a.skode
                FROM dash_ypt_bidang a
                LEFT JOIN (
                    SELECT a.kode_lokasi,p.kode_bidang,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5,
                    CASE WHEN sum(b.$n2)<>0 THEN (sum(b.$n4)/sum(b.$n2))*100 ELSE 0 END AS capai
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    INNER JOIN pp p ON b.kode_pp=p.kode_pp AND b.kode_lokasi=p.kode_lokasi
                    $where $filter_pp
                    GROUP BY a.kode_lokasi,p.kode_bidang
                ) b ON a.kode_lokasi=b.kode_lokasi and a.kode_bidang=b.kode_bidang
                WHERE a.kode_lokasi ='$kode_lokasi' $filter_bidang ";
            }

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select), true);

            $ctg = [];
            $data_realisasi = [];
            $data_anggaran = [];
            foreach ($res as $item) {

                $name = $item['skode'];

                $realisasi = floatval(number_format((float)$item['capai'], 2, '.', ''));
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
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API detail per lembaga
     * 
     */
    public function getDataPerLembaga(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            
            $kode_lokasi = $r->kode_lokasi;
            if (isset($r->kode_bidang) && $r->kode_bidang != "") {
                $filter_bidang = " and a.kode_bidang = '$r->kode_bidang' ";
            } else {
                $filter_bidang = "";
            }
            if (isset($r->kode_pp) && $r->kode_pp != "") {
                $filter_pp = " and b.kode_pp = '$r->kode_pp' ";
            } else {
                $filter_pp = "";
            }
            $col_array = array('periode', 'kode_grafik');
            $db_col_name = array('b.periode', 'a.kode_grafik');
            $where = "WHERE a.kode_lokasi ='$kode_lokasi' AND a.kode_fs='FS1' ";
            $where = $this->filterReq($r, $col_array, $db_col_name, $where, "");

            if (isset($r->jenis) && $r->jenis != "") {
                if ($r->jenis == "PRD") {
                    $n4 = "n6";
                    $n2 = "n7";
                    $n5 = "n9";
                } else {
                    $n4 = "n4";
                    $n2 = "n2";
                    $n5 = "n5";
                }
            } else {
                $n4 = "n4";
                $n2 = "n2";
                $n5 = "n5";
            }

            if (isset($r->kode_grafik) && $r->kode_grafik[1] == "PI04") {
                $sql = "SELECT a.kode_bidang, a.nama, ISNULL(b.n2,0) AS n2, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
                ISNULL(b.capai,0) as capai,a.skode
                FROM dash_ypt_bidang a
                LEFT JOIN (
                    SELECT a.kode_lokasi,p.kode_bidang,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5,
                    CASE WHEN sum(b.$n4)<>0 THEN (sum(b.$n2)/sum(b.$n4))*100 ELSE 0 END AS capai
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    INNER JOIN pp p ON b.kode_pp=p.kode_pp AND b.kode_lokasi=p.kode_lokasi
                    $where $filter_pp
                    GROUP BY a.kode_lokasi,p.kode_bidang
                ) b ON a.kode_lokasi=b.kode_lokasi and a.kode_bidang=b.kode_bidang
                WHERE a.kode_lokasi ='$kode_lokasi' $filter_bidang";
            } else {
                $sql = "SELECT a.kode_bidang, a.nama, ISNULL(b.n2,0) AS n2, ISNULL(b.n4,0) AS n4, ISNULL(b.n5,0) AS n5, 
                ISNULL(b.capai,0) as capai,a.skode
                FROM dash_ypt_bidang a
                LEFT JOIN (
                    SELECT a.kode_lokasi,p.kode_bidang,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n2 ELSE b.$n2 END) AS n2,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n4 ELSE b.$n4 END) AS n4,
                    SUM(CASE WHEN b.jenis_akun='Pendapatan' THEN -b.$n5 ELSE b.$n5 END) AS n5,
                    CASE WHEN sum(b.$n2)<>0 THEN (sum(b.$n4)/sum(b.$n2))*100 ELSE 0 END AS capai
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    INNER JOIN pp p ON b.kode_pp=p.kode_pp AND b.kode_lokasi=p.kode_lokasi
                    $where $filter_pp
                    GROUP BY a.kode_lokasi,p.kode_bidang
                ) b ON a.kode_lokasi=b.kode_lokasi and a.kode_bidang=b.kode_bidang
                WHERE a.kode_lokasi ='$kode_lokasi' $filter_bidang";
            }

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select), true);

            $total = 0;
            foreach ($res as $item) {
                $total = $total + floatval(abs($item['n4']));
            }

            $chart = [];
            $idx = 0;
            if ($total > 0) {
                foreach ($res as $item) {
                    $persen = (floatval($item['n4']) / $total) * 100;
                    $_persen = number_format((float)$persen, 2, '.', '');

                    $name = $item['skode'];

                    if ($idx == 0) {
                        if ($_persen < 0) {
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
                        } else {

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
                        if ($_persen < 0) {
                            $value = [
                                'name' => $name,
                                'y' => abs(floatval($_persen)),
                                'negative' => true,
                                'fillColor' => 'url(#custom-pattern)',
                                'color' => 'url(#custom-pattern)',
                                'nilai' => $item['n4']
                            ];
                        } else {
                            $value = [
                                'name' => $name,
                                'y' => floatval($_persen), 'negative' => false,
                                'nilai' => $item['n4']
                            ];
                        }
                    }
                    array_push($chart, $value);
                    $idx++;
                }
            } else {
                foreach ($res as $item) {
                    $_persen = 0;
                    $name = $item['skode'];
                    if ($_persen < 0) {
                        $value = [
                            'name' => $name,
                            'y' => abs(floatval($_persen)),
                            'negative' => true,
                            'fillColor' => 'url(#custom-pattern)',
                            'color' => 'url(#custom-pattern)',
                            'nilai' => $item['n4']
                        ];
                    } else {
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
            $success['data'] = "Error " . $e;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Function ini untuk API detail kelompok Yoy
     * 
     */

    public function getDataKelompokYoy(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            // $col_array = array('kode_grafik');
            // $db_col_name = array('a.kode_grafik');
            $kode_lokasi = $r->kode_lokasi;
            $kode_grafik1 = $r->query('kode_grafik')[1];
            if ($r->query('kode_grafik')[1] == "PI01") {
                $kode_grafik2 = "PI05";
            } elseif ($r->query('kode_grafik')[1] == "PI02") {
                $kode_grafik2 = "PI06";
            } else {
                $kode_grafik2 = $r->query('kode_grafik')[1];
            }

            $where = "WHERE a.kode_grafik = '" . $kode_grafik2 . "' and a.kode_fs='FS1' ";

            $tahun = intval($r->query('periode')[1]);
            $periode = [];
            for ($i = 0; $i <= 5; $i++) {
                if ($i == 0) {
                    array_push($periode, $tahun);
                } else {
                    $tahun = $tahun - 100;
                    array_push($periode, $tahun);
                }
            }

            if (isset($r->jenis) && $r->jenis != "") {
                if ($r->jenis == "PRD") {
                    $n4 = "n6";
                } else {
                    $n4 = "n4";
                }
            } else {
                $n4 = "n4";
            }


            if (isset($r->kode_bidang) && $r->kode_bidang != "") {
                $kode_bidang = $r->kode_bidang;
                if(isset($r->kode_pp) && $r->kode_pp != ""){
                    $kode_pp = $r->kode_pp;
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
                            LEFT JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND b.kode_lokasi='$kode_lokasi' and b.kode_pp='$kode_pp' AND a.kode_fs=b.kode_fs AND b.periode='" . $periode[5] . "'
                            LEFT JOIN exs_neraca_pp c ON a.kode_neraca=c.kode_neraca AND c.kode_lokasi='$kode_lokasi' and c.kode_pp='$kode_pp' AND a.kode_fs=c.kode_fs AND c.periode='" . $periode[4] . "'
                            LEFT JOIN exs_neraca_pp d ON a.kode_neraca=d.kode_neraca AND d.kode_lokasi='$kode_lokasi' and d.kode_pp='$kode_pp' AND a.kode_fs=d.kode_fs AND d.periode='" . $periode[3] . "'
                            LEFT JOIN exs_neraca_pp e ON a.kode_neraca=e.kode_neraca AND e.kode_lokasi='$kode_lokasi' and e.kode_pp='$kode_pp' AND a.kode_fs=e.kode_fs AND e.periode='" . $periode[2] . "'
                            LEFT JOIN exs_neraca_pp f ON a.kode_neraca=f.kode_neraca AND f.kode_lokasi='$kode_lokasi' and f.kode_pp='$kode_pp' AND a.kode_fs=f.kode_fs AND f.periode='" . $periode[1] . "'
                            LEFT JOIN exs_neraca_pp g ON a.kode_neraca=g.kode_neraca AND g.kode_lokasi='$kode_lokasi' and g.kode_pp='$kode_pp' AND a.kode_fs=g.kode_fs AND g.periode='" . $periode[0] . "'
                            $where 
                        GROUP BY a.kode_neraca
                    )b ON a.kode_neraca=b.kode_neraca 
                    where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS1' ";
                }else{

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
                            LEFT JOIN exs_neraca_bidang b ON a.kode_neraca=b.kode_neraca AND b.kode_lokasi='$kode_lokasi' and b.kode_bidang='$kode_bidang' AND a.kode_fs=b.kode_fs AND b.periode='" . $periode[5] . "'
                            LEFT JOIN exs_neraca_bidang c ON a.kode_neraca=c.kode_neraca AND c.kode_lokasi='$kode_lokasi' and c.kode_bidang='$kode_bidang' AND a.kode_fs=c.kode_fs AND c.periode='" . $periode[4] . "'
                            LEFT JOIN exs_neraca_bidang d ON a.kode_neraca=d.kode_neraca AND d.kode_lokasi='$kode_lokasi' and d.kode_bidang='$kode_bidang' AND a.kode_fs=d.kode_fs AND d.periode='" . $periode[3] . "'
                            LEFT JOIN exs_neraca_bidang e ON a.kode_neraca=e.kode_neraca AND e.kode_lokasi='$kode_lokasi' and e.kode_bidang='$kode_bidang' AND a.kode_fs=e.kode_fs AND e.periode='" . $periode[2] . "'
                            LEFT JOIN exs_neraca_bidang f ON a.kode_neraca=f.kode_neraca AND f.kode_lokasi='$kode_lokasi' and f.kode_bidang='$kode_bidang' AND a.kode_fs=f.kode_fs AND f.periode='" . $periode[1] . "'
                            LEFT JOIN exs_neraca_bidang g ON a.kode_neraca=g.kode_neraca AND g.kode_lokasi='$kode_lokasi' and g.kode_bidang='$kode_bidang' AND a.kode_fs=g.kode_fs AND g.periode='" . $periode[0] . "'
                            $where 
                        GROUP BY a.kode_neraca
                    )b ON a.kode_neraca=b.kode_neraca 
                    where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS1' ";
                }

            } else {

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
                        LEFT JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND b.kode_lokasi='$kode_lokasi' AND a.kode_fs=b.kode_fs AND b.periode='" . $periode[5] . "'
                        LEFT JOIN exs_neraca c ON a.kode_neraca=c.kode_neraca AND c.kode_lokasi='$kode_lokasi' AND a.kode_fs=c.kode_fs AND c.periode='" . $periode[4] . "'
                        LEFT JOIN exs_neraca d ON a.kode_neraca=d.kode_neraca AND d.kode_lokasi='$kode_lokasi' AND a.kode_fs=d.kode_fs AND d.periode='" . $periode[3] . "'
                        LEFT JOIN exs_neraca e ON a.kode_neraca=e.kode_neraca AND e.kode_lokasi='$kode_lokasi' AND a.kode_fs=e.kode_fs AND e.periode='" . $periode[2] . "'
                        LEFT JOIN exs_neraca f ON a.kode_neraca=f.kode_neraca AND f.kode_lokasi='$kode_lokasi' AND a.kode_fs=f.kode_fs AND f.periode='" . $periode[1] . "'
                        LEFT JOIN exs_neraca g ON a.kode_neraca=g.kode_neraca AND g.kode_lokasi='$kode_lokasi' AND a.kode_fs=g.kode_fs AND g.periode='" . $periode[0] . "'
                        $where
                    GROUP BY a.kode_neraca
                )b ON a.kode_neraca=b.kode_neraca 
                where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS1' ";
            }


            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select), true);

            // $color = ['#1D4ED8', '#EC4899', '#EC4899'];
            $ctg = [];
            for ($i = 0; $i <= 3; $i++) {
                array_unshift($ctg, substr($periode[$i], 0, 4));
            }

            $series = [];
            $drill = [];
            $n3 = 0;
            $n4 = 0;
            $n5 = 0;
            $n6 = 0;
            $i = 0;
            $color =  ["#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1", "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1", "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1", "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1", "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"];
            foreach ($res as $item) {
                $n3 += floatval($item['n3']);
                $n4 += floatval($item['n4']);
                $n5 += floatval($item['n5']);
                $n6 += floatval($item['n6']);

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
            array_unshift(
                $data,
                array(
                    'y' => floatval($n3),
                    'name' => $nama,
                    'drilldown' => $ctg[0]
                ),
                array(
                    'y' => floatval($n4),
                    'name' => $nama,
                    'drilldown' => $ctg[1]
                ),
                array(
                    'y' => floatval($n5),
                    'name' => $nama,
                    'drilldown' => $ctg[2]
                ),
                array(
                    'y' => floatval($n6),
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
                'message' => "Error " . $e,
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
    public function getDataKelompokAkun(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            if ($r->query('kode_grafik')[1] == "PI01") {
                $kode_grafik = "PI05";
            } elseif ($r->query('kode_grafik')[1] == "PI02") {
                $kode_grafik = "PI06";
            } else {
                $kode_grafik = $r->query('kode_grafik')[1];
            }
            if (isset($r->kode_lokasi) && $r->kode_lokasi != "") {
                $lokasi = $r->kode_lokasi;
            } else {
                $lokasi = $kode_lokasi;
            }
            $where = "WHERE a.kode_lokasi = '$lokasi' AND a.kode_grafik = '" . $kode_grafik . "' AND a.kode_fs='FS1'";
            $where = $this->filterReq($r, $col_array, $db_col_name, $where, "");

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
            $res = json_decode(json_encode($select), true);

            $total = 0;
            foreach ($res as $item) {
                $total = $total + floatval(abs($item['n4']));
            }

            $idx = 0;
            $chart = [];
            if ($total > 0) {
                foreach ($res as $item) {
                    $persen = (floatval(abs($item['n4'])) / $total) * 100;
                    $_persen = number_format((float)$persen, 2, '.', '');

                    $data = [
                        'name' => $item['nama'],
                        'y' => floatval($_persen),
                        'z' => intval($item['n4'])
                    ];
                    array_push($chart, $data);
                }
            } else {
                foreach ($res as $item) {
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
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }
    
    public function getDataOR5Tahun(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            // $col_array = array('kode_grafik');
            // $db_col_name = array('a.kode_grafik');
            if ($r->query('kode_grafik')[1] == "PI01") {
                $kode_grafik = "PI05";
            } elseif ($r->query('kode_grafik')[1] == "PI02") {
                $kode_grafik = "PI06";
            } else {
                $kode_grafik = $r->query('kode_grafik')[1];
            }

            if (isset($r->kode_lokasi) && $r->kode_lokasi != "") {
                $filter_lokasi = " and a.kode_lokasi='$r->kode_lokasi'";
            } else {
                $filter_lokasi = "";
            }

            if (isset($r->jenis) && $r->jenis != "") {
                if ($r->jenis == "PRD") {
                    $n4 = "n6";
                } else {
                    $n4 = "n4";
                }
            } else {
                $n4 = "n4";
            }

            $where = "WHERE a.kode_lokasi IN ('11','12','13','14','15') and a.kode_grafik = '" . $kode_grafik . "' and a.kode_fs='FS1' $filter_lokasi ";

            $tahun = intval($r->query('periode')[1]);
            $periode = [];
            for ($i = 0; $i < 5; $i++) {
                if ($i == 0) {
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
                    LEFT JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs AND b.periode='" . $periode[4] . "'
                    LEFT JOIN exs_neraca c ON a.kode_neraca=c.kode_neraca AND a.kode_lokasi=c.kode_lokasi AND a.kode_fs=c.kode_fs AND c.periode='" . $periode[3] . "'
                    LEFT JOIN exs_neraca d ON a.kode_neraca=d.kode_neraca AND a.kode_lokasi=d.kode_lokasi AND a.kode_fs=d.kode_fs AND d.periode='" . $periode[2] . "'
                    LEFT JOIN exs_neraca e ON a.kode_neraca=e.kode_neraca AND a.kode_lokasi=e.kode_lokasi AND a.kode_fs=e.kode_fs AND e.periode='" . $periode[1] . "'
                    LEFT JOIN exs_neraca f ON a.kode_neraca=f.kode_neraca AND a.kode_lokasi=f.kode_lokasi AND a.kode_fs=f.kode_fs AND f.periode='" . $periode[0] . "'
                    $where
                GROUP BY a.kode_neraca,a.kode_lokasi
            )b ON a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi IN ('11','12','13','14','15') and a.kode_fs='FS1' $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select), true);

            // $color = ['#1D4ED8', '#EC4899', '#EC4899'];
            $ctg = [];
            for ($i = 0; $i < 5; $i++) {
                array_unshift($ctg, substr($periode[$i], 0, 4));
            }

            $series = [];
            $drill = [];
            $n1 = 0;
            $n2 = 0;
            $n3 = 0;
            $n4 = 0;
            $n5 = 0;
            $i = 0;
            $color =  ["#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1", "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1", "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1", "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1", "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"];
            foreach ($res as $item) {
                $n1 += floatval($item['n1']);
                $n2 += floatval($item['n2']);
                $n3 += floatval($item['n3']);
                $n4 += floatval($item['n4']);
                $n5 += floatval($item['n5']);

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
            array_unshift(
                $data,
                array(
                    'y' => floatval($n1),
                    'name' => $nama,
                    'drilldown' => $ctg[0]
                ),
                array(
                    'y' => floatval($n2),
                    'name' => $nama,
                    'drilldown' => $ctg[1]
                ),
                array(
                    'y' => floatval($n3),
                    'name' => $nama,
                    'drilldown' => $ctg[2]
                ),
                array(
                    'y' => floatval($n4),
                    'name' => $nama,
                    'drilldown' => $ctg[3]
                ),
                array(
                    'y' => floatval($n5),
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
                'message' => "Error " . $e,
                'kategori' => [],
                'series' => [],
                'drilldown' => []
            ];
            return response()->json($success, $this->successStatus);
        }
    }
}
